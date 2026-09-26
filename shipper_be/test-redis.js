const redis = require('./redis');

async function test() {
    await redis.set('test', 'Hello Redis');

    const value = await redis.get('test');

    console.log('Value:', value);

    await redis.quit();
}

test();