// src/middleware/rate_limit_middleware.js
// Rate limit theo shipper (JWT ma_nhan_vien), 3 bucket:
//  - read     (GET/HEAD): 20/phút  — danh sách đơn, notif, profile, history...
//  - critical (POST orders/:id/{pickup,arrived,delivered,report-failure,
//              finalize-customer-failure,overdue}): 5/phút
//  - minor    (các write còn lại: mark-read, delete notif, profile, logout,
//              status/online): 60/phút
// Store: Redis (ioredis) là chính, memory Map có cleanup làm fallback.
// Vượt → 429 { success:false, code:"TOO_MANY_REQUESTS", message:"..." }
'use strict';

const redis = require('../config/redis');

const WINDOW_MS = 60 * 1000;
const LIMITS = {
    read: 20,
    critical: 5,
    minor: 60,
};

const CRITICAL_PATTERNS = [
    /^\/orders\/[^/]+\/pickup$/,
    /^\/orders\/[^/]+\/arrived$/,
    /^\/orders\/[^/]+\/delivered$/,
    /^\/orders\/[^/]+\/report-failure$/,
    /^\/orders\/[^/]+\/finalize-customer-failure$/,
    /^\/orders\/[^/]+\/overdue$/,
];

const memoryStore = new Map(); // key -> { count, resetAt }
let memoryCleanupTimer = null;

function ensureMemoryCleanup() {
    if (memoryCleanupTimer) return;
    memoryCleanupTimer = setInterval(() => {
        const now = Date.now();
        for (const [k, v] of memoryStore) {
            if (v.resetAt <= now) memoryStore.delete(k);
        }
    }, WINDOW_MS);
    if (memoryCleanupTimer.unref) memoryCleanupTimer.unref();
}

// Trả về path tương đối so với mount /api/shipper.
function getRelativePath(req) {
    const url = req.originalUrl || req.url || '';
    const pathOnly = url.split('?')[0];
    return pathOnly.replace(/^\/api\/shipper/, '');
}

function bucketFor(method, relativePath) {
    if (method === 'GET' || method === 'HEAD') return 'read';
    if (CRITICAL_PATTERNS.some((re) => re.test(relativePath))) return 'critical';
    return 'minor';
}

async function checkRedis(key, windowSec) {
    if (redis.status !== 'ready') return null;
    try {
        const count = await redis.incr(key);
        let ttl;
        if (count === 1) {
            await redis.expire(key, windowSec);
            ttl = windowSec;
        } else {
            ttl = await redis.ttl(key);
            if (ttl < 0) {
                await redis.expire(key, windowSec);
                ttl = windowSec;
            }
        }
        return { count, ttl };
    } catch (_) {
        return null;
    }
}

function checkMemory(key) {
    const now = Date.now();
    let entry = memoryStore.get(key);
    if (!entry || entry.resetAt <= now) {
        entry = { count: 0, resetAt: now + WINDOW_MS };
        memoryStore.set(key, entry);
    }
    entry.count += 1;
    return entry;
}

function createRateLimiter() {
    return async function rateLimitMiddleware(req, res, next) {
        const maNhanVien = req.user && req.user.ma_nhan_vien;
        if (!maNhanVien) return next();

        const bucket = bucketFor(req.method, getRelativePath(req));
        const limit = LIMITS[bucket];
        const windowSec = WINDOW_MS / 1000;
        const key = `rl:${bucket}:${maNhanVien}`;

        const r = await checkRedis(key, windowSec);
        let allowed;
        let retryAfterSec;

        if (r) {
            allowed = r.count <= limit;
            retryAfterSec = r.ttl > 0 ? r.ttl : windowSec;
        } else {
            ensureMemoryCleanup();
            const m = checkMemory(key);
            allowed = m.count <= limit;
            retryAfterSec = Math.max(1, Math.ceil((m.resetAt - Date.now()) / 1000));
        }

        if (!allowed) {
            res.set('Retry-After', String(retryAfterSec));
            return res.status(429).json({
                success: false,
                code: 'TOO_MANY_REQUESTS',
                message: 'Bạn thao tác quá nhanh, vui lòng thử lại sau.',
            });
        }
        next();
    };
}

module.exports = { createRateLimiter };