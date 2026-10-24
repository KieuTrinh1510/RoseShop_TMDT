{{-- Component RoseBot Customer Support Widget --}}
<div id="rosebot-container" class="rosebot-wrapper">
    <!-- Floating Launcher Button -->
    <button id="rosebot-launcher" class="rosebot-launcher" aria-label="Mở trợ lý ảo RoseBot" title="Trò chuyện với RoseBot">
        <div class="launcher-pulse"></div>
        <div class="launcher-avatar-wrap">
            <img src="{{ asset('img/chatbot/rosebot-avatar.jpg') }}" alt="RoseBot Advisor" class="launcher-avatar-img">
            <span class="close-icon"><i class="fa-solid fa-xmark"></i></span>
        </div>
        <div class="launcher-badge" id="rosebot-badge">AI</div>
        <div class="launcher-tooltip" id="rosebot-tooltip">
            <span>Chào bạn! Cần RoseBot tư vấn hoa không? ✨</span>
        </div>
    </button>

    <!-- Chat Modal Window -->
    <div id="rosebot-modal" class="rosebot-modal" role="dialog" aria-modal="true" aria-labelledby="rosebot-title">
        <!-- Header -->
        <div class="rosebot-header">
            <div class="header-info">
                <div class="bot-avatar-wrapper">
                    <div class="bot-avatar">
                        <img src="{{ asset('img/chatbot/rosebot-avatar.jpg') }}" alt="RoseBot Advisor" class="bot-avatar-img">
                    </div>
                    <span class="status-indicator"></span>
                </div>
                <div>
                    <h3 id="rosebot-title" class="bot-title">RoseBot <span class="ai-pill">AI Advisor</span></h3>
                    <p class="bot-status-text">Tư vấn chọn hoa &amp; Chăm sóc khách hàng</p>
                </div>
            </div>
            <div class="header-actions">
                <button type="button" id="rosebot-btn-reset" class="header-btn" title="Làm mới cuộc trò chuyện" aria-label="Làm mới">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button type="button" id="rosebot-btn-close" class="header-btn" title="Đóng cửa sổ chat" aria-label="Đóng">
                    <i class="fa-solid fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Chat Body / Messages Scroll Area -->
        <div id="rosebot-messages" class="rosebot-messages">
            <!-- Welcome Message -->
            <div class="message-group bot-group">
                <div class="msg-avatar">
                    <img src="{{ asset('img/chatbot/rosebot-avatar.jpg') }}" alt="RoseBot Advisor" class="msg-avatar-img">
                </div>
                <div class="msg-bubble bot-bubble">
                    <p>Xin chào! Mình là <strong>RoseBot</strong> – Chuyên viên tư vấn AI của <strong>RoseShop</strong> 🌸✨</p>
                    <p>Mình có thể giúp bạn chọn mẫu hoa phù hợp cho từng dịp, tra cứu tiến độ đơn hàng hoặc hướng dẫn bạn tự thiết kế bó hoa theo sở thích. Bạn muốn tìm hiểu gì hôm nay?</p>
                </div>
            </div>

            <!-- Quick Suggestion Prompts -->
            <div id="rosebot-quick-chips" class="quick-chips-wrapper">
                <p class="chips-label"><i class="fa-regular fa-lightbulb"></i> Gợi ý nhanh cho bạn:</p>
                <div class="chips-container">
                    <button type="button" class="quick-chip" data-prompt="Tư vấn cho mình bó hoa sinh nhật tặng bạn gái giá tầm 500k">
                        🌸 Hoa sinh nhật bạn gái
                    </button>
                    <button type="button" class="quick-chip" data-prompt="Chính sách giao hàng hỏa tốc và đổi trả khi hoa hỏng như thế nào?">
                        🚚 Chính sách ship & đổi trả
                    </button>
                    <button type="button" class="quick-chip" data-prompt="Kiểm tra giúp mình đơn hàng gần nhất của tôi">
                        📦 Tra cứu đơn hàng
                    </button>
                    <button type="button" class="quick-chip" data-prompt="Hướng dẫn mình cách tự mix bó hoa theo ý thích và tính phụ phí?">
                        🎨 Tự mix bó hoa Custom
                    </button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="rosebot-typing" class="rosebot-typing is-hidden">
            <div class="msg-avatar small">
                <img src="{{ asset('img/chatbot/rosebot-avatar.jpg') }}" alt="RoseBot Advisor" class="msg-avatar-img">
            </div>
            <div class="typing-bubble">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-text">RoseBot đang tìm kiếm và trả lời...</span>
        </div>

        <!-- Footer / Input Form -->
        <div class="rosebot-footer">
            <form id="rosebot-form" class="rosebot-input-form" onsubmit="return false;">
                <input 
                    type="text" 
                    id="rosebot-input" 
                    class="rosebot-input" 
                    placeholder="Hỏi RoseBot về hoa tươi, mã đơn hàng..." 
                    autocomplete="off" 
                    maxlength="500" 
                />
                <button type="submit" id="rosebot-btn-send" class="rosebot-btn-send" aria-label="Gửi tin nhắn" disabled>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
            <div class="rosebot-footnote">
                <span>Được hỗ trợ bởi AI &amp; Hệ thống RAG RoseShop</span>
            </div>
        </div>
    </div>
</div>

<style>
/* ========================================================
   ROSEBOT STYLES - MODERN, LUXURIOUS & RESPONSIVE
   ======================================================== */
.rosebot-wrapper {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    font-family: var(--font-main, 'Be Vietnam Pro', sans-serif);
}

/* --- Floating Launcher Button --- */
.rosebot-launcher {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 60%, #9f1239 100%);
    border: none;
    box-shadow: 0 8px 24px rgba(225, 29, 72, 0.4), 0 2px 6px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
    outline: none;
}

.rosebot-launcher:hover {
    transform: scale(1.08);
    box-shadow: 0 12px 30px rgba(225, 29, 72, 0.55), 0 4px 10px rgba(0, 0, 0, 0.15);
}

.rosebot-launcher:active {
    transform: scale(0.95);
}

.launcher-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid rgba(225, 29, 72, 0.6);
    animation: rosebotPulse 2.5s infinite;
    pointer-events: none;
}

@keyframes rosebotPulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.35); opacity: 0; }
    100% { transform: scale(1.35); opacity: 0; }
}

.launcher-avatar-wrap {
    position: relative;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.95);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
}

.launcher-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.launcher-avatar-wrap .close-icon {
    display: none;
    font-size: 22px;
    color: #ffffff;
}

.rosebot-wrapper.is-open .launcher-avatar-img {
    display: none;
}

.rosebot-wrapper.is-open .launcher-avatar-wrap {
    background: transparent;
    border-color: transparent;
    box-shadow: none;
}

.rosebot-wrapper.is-open .launcher-avatar-wrap .close-icon {
    display: block;
}

.launcher-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #10b981;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    letter-spacing: 0.5px;
}

/* Tooltip */
.launcher-tooltip {
    position: absolute;
    right: 74px;
    top: 50%;
    transform: translateY(-50%);
    background: #ffffff;
    color: #1f2937;
    font-size: 13px;
    font-weight: 500;
    padding: 8px 14px;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, transform 0.3s ease;
    border: 1px solid rgba(225, 29, 72, 0.15);
    pointer-events: none;
}

.launcher-tooltip::after {
    content: '';
    position: absolute;
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
    border-width: 6px 0 6px 6px;
    border-style: solid;
    border-color: transparent transparent transparent #ffffff;
}

.rosebot-launcher:hover .launcher-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(-50%) translateX(-4px);
}

.rosebot-wrapper.is-open .launcher-tooltip {
    display: none !important;
}

/* --- Chat Modal Window --- */
.rosebot-modal {
    position: absolute;
    bottom: 76px;
    right: 0;
    width: 380px;
    height: 580px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(225, 29, 72, 0.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.96);
    transform-origin: bottom right;
    transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.3s;
}

.rosebot-wrapper.is-open .rosebot-modal {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

/* Header */
.rosebot-header {
    background: linear-gradient(135deg, #be123c 0%, #9f1239 100%);
    padding: 16px 18px;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(159, 18, 57, 0.15);
}

.header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bot-avatar-wrapper {
    position: relative;
    width: 42px;
    height: 42px;
}

.bot-avatar {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.85);
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
}

.bot-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 11px;
    height: 11px;
    background: #10b981;
    border: 2px solid #ffffff;
    border-radius: 50%;
}

.bot-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ai-pill {
    font-size: 9px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.25);
    padding: 2px 6px;
    border-radius: 6px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.bot-status-text {
    margin: 2px 0 0 0;
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.85);
}

.header-actions {
    display: flex;
    gap: 6px;
}

.header-btn {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s ease, transform 0.1s ease;
}

.header-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.header-btn:active {
    transform: scale(0.92);
}

/* Chat Messages Body */
.rosebot-messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #fdfafb;
    scroll-behavior: smooth;
}

.rosebot-messages::-webkit-scrollbar {
    width: 5px;
}
.rosebot-messages::-webkit-scrollbar-thumb {
    background: rgba(225, 29, 72, 0.2);
    border-radius: 4px;
}

/* Message Groups */
.message-group {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    animation: fadeInMsg 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInMsg {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.message-group.user-group {
    flex-direction: row-reverse;
}

.msg-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #ffe4e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(225, 29, 72, 0.12);
    border: 1.5px solid #ffe4e6;
    overflow: hidden;
}

.msg-avatar.small {
    width: 26px;
    height: 26px;
    font-size: 13px;
}

.msg-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 50%;
}

.msg-bubble {
    max-width: 82%;
    padding: 12px 15px;
    border-radius: 18px;
    font-size: 13.5px;
    line-height: 1.55;
    position: relative;
    word-break: break-word;
}

.bot-bubble {
    background: #ffffff;
    color: #1f2937;
    border-top-left-radius: 4px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.04);
    border: 1px solid #f3e8eb;
}

.bot-bubble p {
    margin: 0 0 8px 0;
}
.bot-bubble p:last-child {
    margin-bottom: 0;
}

.bot-bubble ul {
    margin: 6px 0 8px 18px;
    padding: 0;
}

.bot-bubble li {
    margin-bottom: 4px;
}

.bot-bubble strong {
    color: #9f1239;
}

.bot-bubble a {
    color: #be123c;
    text-decoration: underline;
    font-weight: 600;
}

.user-bubble {
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    color: #ffffff;
    border-top-right-radius: 4px;
    box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
}

.user-bubble p {
    margin: 0;
}

/* Suggested Product Cards */
.suggested-cards-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 10px;
}

.suggested-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff5f7;
    border: 1px solid #fecdd3;
    padding: 10px 13px;
    border-radius: 12px;
    text-decoration: none;
    color: #881337;
    transition: all 0.2s ease;
}

.suggested-card:hover {
    background: #ffe4e8;
    transform: translateX(4px);
    border-color: #fda4af;
}

.card-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.card-title {
    font-weight: 600;
    font-size: 13px;
    color: #9f1239;
}

.card-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
    flex-wrap: wrap;
}

.card-price {
    font-size: 12px;
    font-weight: 700;
    color: #e11d48;
}

.card-size {
    font-size: 10.5px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 1px 5px;
    border-radius: 4px;
}

.badge-stock {
    font-size: 10.5px;
    padding: 1px 6px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.badge-in-stock {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

.badge-out-stock {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.card-arrow {
    font-size: 12px;
    color: #be123c;
}

/* Custom Mix Proposal Box */
.custom-mix-proposal-box {
    margin-top: 10px;
    background: #ffffff;
    border: 1.5px solid #fda4af;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 4px 12px rgba(225, 29, 72, 0.08);
}

.mix-proposal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.mix-tag {
    font-size: 11px;
    font-weight: 700;
    color: #be123c;
    background: #fff1f2;
    padding: 3px 8px;
    border-radius: 20px;
    border: 1px solid #fecdd3;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.mix-price {
    font-size: 13.5px;
    font-weight: 800;
    color: #e11d48;
}

.mix-proposal-title {
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 6px;
}

.mix-proposal-specs {
    display: flex;
    gap: 10px;
    font-size: 11.5px;
    color: #4b5563;
    padding: 4px 8px;
    background: #fdf2f4;
    border-radius: 6px;
    margin-bottom: 8px;
}

.mix-proposal-ingredients {
    font-size: 11.5px;
    color: #374151;
    margin-bottom: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mix-ingredient-row {
    display: flex;
    justify-content: space-between;
}

.btn-apply-mix {
    width: 100%;
    padding: 9px 12px;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(225, 29, 72, 0.25);
}

.btn-apply-mix:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(225, 29, 72, 0.35);
}

.btn-applied-builder {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 8px 14px;
    background: #059669;
    color: #ffffff !important;
    text-decoration: none !important;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
}

.btn-applied-builder:hover {
    background: #047857;
}

/* Markdown element styling in bot bubble */
.bot-bubble h3, .bot-bubble h4 {
    margin: 8px 0 4px 0;
    color: #9f1239;
    font-weight: 700;
}
.bot-bubble h3 { font-size: 14.5px; }
.bot-bubble h4 { font-size: 13.5px; }

.bot-bubble hr.bot-divider {
    border: none;
    border-top: 1px dashed #fecdd3;
    margin: 8px 0;
}

.bot-bubble ol {
    margin: 6px 0;
    padding-left: 20px;
}
.bot-bubble ol li {
    margin-bottom: 4px;
}

.bot-bubble ul {
    margin: 6px 0;
    padding-left: 18px;
}
.bot-bubble ul li {
    margin-bottom: 4px;
}

/* Quick Chips */
.quick-chips-wrapper {
    margin-top: 6px;
    padding: 10px;
    background: #ffffff;
    border-radius: 14px;
    border: 1px dashed #f43f5e;
}

.chips-label {
    margin: 0 0 8px 0;
    font-size: 11.5px;
    font-weight: 600;
    color: #be123c;
    display: flex;
    align-items: center;
    gap: 5px;
}

.chips-container {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.quick-chip {
    text-align: left;
    background: #fff1f2;
    border: 1px solid #ffe4e6;
    color: #9f1239;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.quick-chip:hover {
    background: #ffe4e6;
    border-color: #fca5a5;
    transform: translateX(3px);
}

/* Typing Indicator */
.rosebot-typing {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #fdfafb;
    border-top: 1px solid #f3e8eb;
}

.rosebot-typing.is-hidden {
    display: none;
}

.typing-bubble {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    padding: 6px 12px;
    border-radius: 14px;
    border: 1px solid #f3e8eb;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.typing-bubble span {
    width: 6px;
    height: 6px;
    background: #e11d48;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite ease-in-out both;
}

.typing-bubble span:nth-child(1) { animation-delay: -0.32s; }
.typing-bubble span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

.typing-text {
    font-size: 11.5px;
    color: #6b7280;
    font-style: italic;
}

/* Footer / Input Form */
.rosebot-footer {
    padding: 12px 14px;
    background: #ffffff;
    border-top: 1px solid #f3e8eb;
}

.rosebot-input-form {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fdf2f4;
    border: 1px solid #fecdd3;
    border-radius: 24px;
    padding: 4px 6px 4px 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.rosebot-input-form:focus-within {
    border-color: #e11d48;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
    background: #ffffff;
}

.rosebot-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 8px 0;
    font-size: 13.5px;
    color: #1f2937;
    outline: none;
    font-family: inherit;
}

.rosebot-input::placeholder {
    color: #9ca3af;
}

.rosebot-btn-send {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e11d48;
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.rosebot-btn-send:hover:not(:disabled) {
    background: #be123c;
    transform: scale(1.05);
}

.rosebot-btn-send:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    opacity: 0.6;
}

.rosebot-footnote {
    text-align: center;
    margin-top: 6px;
}

.rosebot-footnote span {
    font-size: 10px;
    color: #9ca3af;
}

/* Mobile responsive */
@media (max-width: 480px) {
    .rosebot-wrapper {
        bottom: 16px;
        right: 16px;
    }
    .rosebot-modal {
        width: calc(100vw - 32px);
        height: calc(100vh - 100px);
        bottom: 70px;
        right: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const launcher = document.getElementById('rosebot-launcher');
    const container = document.getElementById('rosebot-container');
    const modal = document.getElementById('rosebot-modal');
    const btnClose = document.getElementById('rosebot-btn-close');
    const btnReset = document.getElementById('rosebot-btn-reset');
    const form = document.getElementById('rosebot-form');
    const input = document.getElementById('rosebot-input');
    const btnSend = document.getElementById('rosebot-btn-send');
    const messagesBox = document.getElementById('rosebot-messages');
    const typingIndicator = document.getElementById('rosebot-typing');
    const quickChips = document.querySelectorAll('.quick-chip');

    let isSending = false;

    // Toggle Modal
    launcher.addEventListener('click', function() {
        container.classList.toggle('is-open');
        if (container.classList.contains('is-open')) {
            setTimeout(() => input.focus(), 250);
            scrollToBottom();
        }
    });

    btnClose.addEventListener('click', function() {
        container.classList.remove('is-open');
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && container.classList.contains('is-open')) {
            container.classList.remove('is-open');
        }
    });

    // Enable/disable send button
    input.addEventListener('input', function() {
        btnSend.disabled = input.value.trim().length === 0 || isSending;
    });

    // Handle Quick Chips click
    quickChips.forEach(chip => {
        chip.addEventListener('click', function() {
            const promptText = this.getAttribute('data-prompt');
            if (promptText && !isSending) {
                input.value = promptText;
                submitMessage(promptText);
            }
        });
    });

    const BOT_AVATAR_HTML = `<img src="{{ asset('img/chatbot/rosebot-avatar.jpg') }}" alt="RoseBot Advisor" class="msg-avatar-img">`;

    // Reset Chat Session
    btnReset.addEventListener('click', function() {
        if (confirm('Bạn có muốn làm mới cuộc trò chuyện với RoseBot không?')) {
            fetch('{{ route("chatbot.reset") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            }).then(() => {
                messagesBox.innerHTML = `
                    <div class="message-group bot-group">
                        <div class="msg-avatar">
                            ${BOT_AVATAR_HTML}
                        </div>
                        <div class="msg-bubble bot-bubble">
                            <p>Cuộc hội thoại đã được làm mới! RoseBot có thể hỗ trợ gì cho bạn ngay bây giờ? 🌹</p>
                        </div>
                    </div>
                `;
                input.value = '';
                btnSend.disabled = true;
            });
        }
    });

    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (text && !isSending) {
            submitMessage(text);
        }
    });

    function submitMessage(text) {
        isSending = true;
        btnSend.disabled = true;
        input.value = '';

        // 1. Render User Message
        appendMessage('user', text);
        scrollToBottom();

        // 2. Show Typing Indicator
        typingIndicator.classList.remove('is-hidden');
        scrollToBottom();

        // 3. Call API
        fetch('{{ route("chatbot.message") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            typingIndicator.classList.add('is-hidden');
            isSending = false;
            btnSend.disabled = input.value.trim().length === 0;

            if (data.success) {
                appendMessage(
                    'bot', 
                    data.reply, 
                    data.suggested_items, 
                    data.custom_mix_proposal, 
                    data.applied_custom_mix, 
                    data.builder_url
                );
            } else {
                appendMessage('bot', data.message || 'Dạ xin lỗi bạn, hệ thống đang bận một chút xíu. Bạn thử lại nhé!');
            }
            scrollToBottom();
        })
        .catch(err => {
            console.error('Chatbot error:', err);
            typingIndicator.classList.add('is-hidden');
            isSending = false;
            btnSend.disabled = input.value.trim().length === 0;
            appendMessage('bot', 'Dạ kết nối mạng đang bị gián đoạn, bạn vui lòng kiểm tra lại đường truyền nhé! 🌸');
            scrollToBottom();
        });
    }

    function appendMessage(sender, text, suggestedItems = [], customMixProposal = null, appliedCustomMix = false, builderUrl = '') {
        const group = document.createElement('div');
        group.className = `message-group ${sender}-group`;

        const avatar = document.createElement('div');
        avatar.className = 'msg-avatar';
        avatar.innerHTML = sender === 'bot' ? BOT_AVATAR_HTML : '<i class="fa-solid fa-user"></i>';

        const bubble = document.createElement('div');
        bubble.className = `msg-bubble ${sender}-bubble`;

        if (sender === 'bot') {
            bubble.innerHTML = formatMarkdown(text);

            // Render Suggested Cards if any
            if (suggestedItems && suggestedItems.length > 0) {
                const cardsWrap = document.createElement('div');
                cardsWrap.className = 'suggested-cards-container';
                suggestedItems.forEach(item => {
                    const card = document.createElement('a');
                    card.className = 'suggested-card';
                    card.href = item.url || '#';
                    const priceFormatted = item.price ? Number(item.price).toLocaleString('vi-VN') + 'đ' : '';
                    const stockStatus = item.stock_status || (item.in_stock ? 'Còn hàng' : 'Hết hàng');
                    const stockBadge = stockStatus === 'Còn hàng'
                        ? `<span class="badge-stock badge-in-stock"><i class="fa-solid fa-check"></i> Còn hàng</span>`
                        : `<span class="badge-stock badge-out-stock"><i class="fa-solid fa-circle-exclamation"></i> ${escapeHtml(stockStatus)}</span>`;
                    
                    card.innerHTML = `
                        <div class="card-info">
                            <span class="card-title">${escapeHtml(item.title)}</span>
                            <div class="card-meta">
                                ${priceFormatted ? `<span class="card-price">${priceFormatted}</span>` : ''}
                                ${stockBadge}
                                ${item.size ? `<span class="card-size">Size ${escapeHtml(item.size)}</span>` : ''}
                            </div>
                        </div>
                        <span class="card-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    `;
                    cardsWrap.appendChild(card);
                });
                bubble.appendChild(cardsWrap);
            }

            // Render Custom Mix Proposal Card if available
            if (customMixProposal && customMixProposal.thanh_phan && customMixProposal.thanh_phan.length > 0) {
                const mixWrap = document.createElement('div');
                mixWrap.className = 'custom-mix-proposal-box';
                const estPrice = customMixProposal.gia_uoc_tinh 
                    ? Number(customMixProposal.gia_uoc_tinh).toLocaleString('vi-VN') + 'đ' 
                    : 'Liên hệ';
                
                let ingredientsHtml = '';
                customMixProposal.thanh_phan.forEach(tp => {
                    const roleName = tp.vai_tro === 'HOA_CHINH' ? 'Hoa chính' : (tp.vai_tro === 'HOA_PHU' ? 'Hoa phụ' : (tp.vai_tro === 'GIAY_GOI' ? 'Giấy gói' : (tp.vai_tro === 'RUY_BANG' ? 'Ruy băng' : 'Phụ kiện')));
                    ingredientsHtml += `
                        <div class="mix-ingredient-row">
                            <span class="ing-name">🌸 <strong>${escapeHtml(tp.ten_san_pham)}</strong> (${roleName})</span>
                            <span class="ing-qty">x${tp.so_luong}</span>
                        </div>
                    `;
                });

                mixWrap.innerHTML = `
                    <div class="mix-proposal-header">
                        <span class="mix-tag"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Thiết kế Custom Mix</span>
                        <span class="mix-price">${estPrice}</span>
                    </div>
                    <div class="mix-proposal-title">${escapeHtml(customMixProposal.ten_bo_hoa || 'Bó hoa Custom Mix AI')}</div>
                    <div class="mix-proposal-specs">
                        <span><strong>Dáng:</strong> ${escapeHtml(customMixProposal.kieu_bo_hoa || 'TRON')}</span>
                        <span><strong>Size:</strong> ${escapeHtml(customMixProposal.size || 'M')}</span>
                        <span><strong>Số bông:</strong> ${customMixProposal.so_bong || 10}</span>
                    </div>
                    <div class="mix-proposal-ingredients">
                        ${ingredientsHtml}
                    </div>
                    <button type="button" class="btn-apply-mix">
                        <i class="fa-solid fa-palette"></i> Áp dụng vào Custom Mix Builder
                    </button>
                `;

                bubble.appendChild(mixWrap);

                const btnApply = mixWrap.querySelector('.btn-apply-mix');
                btnApply.addEventListener('click', function() {
                    btnApply.disabled = true;
                    btnApply.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang nạp vào Builder...';

                    fetch('{{ route("chatbot.apply-custom-mix") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ custom_mix: customMixProposal })
                    })
                    .then(res => res.json())
                    .then(resData => {
                        if (resData.success && resData.redirect_url) {
                            window.location.href = resData.redirect_url;
                        } else {
                            alert(resData.message || 'Không thể nạp cấu hình Custom Mix. Xin thử lại!');
                            btnApply.disabled = false;
                            btnApply.innerHTML = '<i class="fa-solid fa-palette"></i> Áp dụng vào Custom Mix Builder';
                        }
                    })
                    .catch(err => {
                        console.error('Error applying custom mix:', err);
                        btnApply.disabled = false;
                        btnApply.innerHTML = '<i class="fa-solid fa-palette"></i> Áp dụng vào Custom Mix Builder';
                    });
                });
            }

            // If applied via chat text, show a direct button to open the builder
            if (appliedCustomMix) {
                const navBox = document.createElement('div');
                navBox.className = 'applied-mix-action';
                navBox.innerHTML = `
                    <a href="${builderUrl || '{{ route("customer.bo-hoa.tu-tao") }}'}" class="btn-applied-builder">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Mở Custom Mix Builder ngay
                    </a>
                `;
                bubble.appendChild(navBox);
            }
        } else {
            bubble.innerHTML = `<p>${escapeHtml(text)}</p>`;
        }

        group.appendChild(avatar);
        group.appendChild(bubble);
        messagesBox.appendChild(group);
    }

    function formatMarkdown(text) {
        if (!text) return '';

        // 1. Unescape backslashes before special markdown characters (\###, \---, \*, \., \-, \_)
        let clean = text.replace(/\\([#\-\*\.\_\+!\[\]\(\)\\])/g, '$1');

        // 2. Decode raw HTML character entities (hex &#x20;, &#x48; & dec &#32;)
        clean = clean.replace(/&#x([0-9a-fA-F]+);/g, (_, hex) => String.fromCharCode(parseInt(hex, 16)));
        clean = clean.replace(/&#([0-9]+);/g, (_, dec) => String.fromCharCode(parseInt(dec, 10)));

        // 3. Prevent XSS: encode &, <, >, " before parsing markdown
        clean = clean
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        // 4. Safe Links: [title](url)
        clean = clean.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, linkText, url) => {
            const trimmedUrl = url.trim();
            if (trimmedUrl.startsWith('/') || trimmedUrl.startsWith('https://') || trimmedUrl.startsWith('http://')) {
                return `<a href="${trimmedUrl}" target="_blank" rel="noopener noreferrer">${linkText}</a>`;
            }
            return linkText;
        });

        // 5. Bold & Italic
        clean = clean.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
        clean = clean.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        clean = clean.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // 6. Split by lines and process headings, horizontal rules, lists, paragraphs
        const rawLines = clean.split('\n');
        let output = [];
        let inUl = false;
        let inOl = false;

        function closeLists() {
            if (inUl) { output.push('</ul>'); inUl = false; }
            if (inOl) { output.push('</ol>'); inOl = false; }
        }

        rawLines.forEach(line => {
            const trimmed = line.trim();

            // Horizontal rule
            if (/^(\-{3,}|\*{3,}|_{3,})$/.test(trimmed)) {
                closeLists();
                output.push('<hr class="bot-divider" />');
                return;
            }

            // Heading 3: ###
            if (trimmed.startsWith('### ')) {
                closeLists();
                output.push(`<h4>${trimmed.substring(4)}</h4>`);
                return;
            }

            // Heading 2: ##
            if (trimmed.startsWith('## ')) {
                closeLists();
                output.push(`<h3>${trimmed.substring(3)}</h3>`);
                return;
            }

            // Heading 1: #
            if (trimmed.startsWith('# ')) {
                closeLists();
                output.push(`<h3>${trimmed.substring(2)}</h3>`);
                return;
            }

            // Ordered list: 1. or 1\. (which was unescaped to 1.)
            const olMatch = trimmed.match(/^(\d+)\.\s+(.*)$/);
            if (olMatch) {
                if (inUl) { output.push('</ul>'); inUl = false; }
                if (!inOl) { output.push('<ol>'); inOl = true; }
                output.push(`<li>${olMatch[2]}</li>`);
                return;
            }

            // Unordered list: * or - or •
            const ulMatch = trimmed.match(/^[\*\-•]\s+(.*)$/);
            if (ulMatch) {
                if (inOl) { output.push('</ol>'); inOl = false; }
                if (!inUl) { output.push('<ul>'); inUl = true; }
                output.push(`<li>${ulMatch[1]}</li>`);
                return;
            }

            // Regular line
            closeLists();
            if (trimmed.length > 0) {
                output.push(`<p>${trimmed}</p>`);
            }
        });

        closeLists();
        return output.join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function scrollToBottom() {
        setTimeout(() => {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }, 50);
    }
});
</script>
