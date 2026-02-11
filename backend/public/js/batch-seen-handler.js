const BATCH_CONFIG = {
  ENABLED: true,
  MAX_BATCH_SIZE: 20,
  BATCH_INTERVAL: 3000,
};

let seenQueue = [];

function initBatchSeenHandler() {
  if (!BATCH_CONFIG.ENABLED) return;
  setInterval(processBatch, BATCH_CONFIG.BATCH_INTERVAL);
  window.batchSeenHandler = { addToQueue: addToSeenQueue, processBatch: processBatch };
}

function addToSeenQueue(messageId) {
  if (!BATCH_CONFIG.ENABLED) return;
  if (seenQueue.includes(messageId)) return;
  seenQueue.push(messageId);
  if (seenQueue.length >= BATCH_CONFIG.MAX_BATCH_SIZE) processBatch();
}

function processBatch() {
  if (!BATCH_CONFIG.ENABLED || seenQueue.length === 0) return;

  const userId = window.currentUserId || window.userId;
  const isGroupChat = window.location.pathname.includes("/group/");
  const otherUserId = isGroupChat ? (window.groupId) : (window.otherUserId);

  if (!userId || !otherUserId) return;

  const batch = [...seenQueue];
  seenQueue = [];

  const apiUrl = window.getApiUrl ? window.getApiUrl('markMessagesBatchAsSeen') : '/chat-app/backend/api/markMessagesBatchAsSeen';

  fetch(apiUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      message_ids: batch,
      user_id: userId,
      other_user_id: otherUserId,
      is_group: isGroupChat,
    }),
  }).catch((error) => console.warn("Batch seen error:", error));
}

document.addEventListener("DOMContentLoaded", initBatchSeenHandler);