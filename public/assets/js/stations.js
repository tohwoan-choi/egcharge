// stations.js

// 사용자별 반응 상태 저장 (실제로는 서버에서 관리)
let userReactions = {};

document.addEventListener('DOMContentLoaded', function() {
  // 한줄평 폼 이벤트 리스너
  const reviewForm = document.getElementById('review-form');
  if (reviewForm) {
    reviewForm.addEventListener('submit', handleReviewSubmit);

    const reviewContent = document.getElementById('review-content');
    if (reviewContent) {
      reviewContent.addEventListener('input', updateCharCount);
    }
  }

  // 모달 외부 클릭시 닫기
  window.addEventListener('click', function(event) {
    const modal = document.getElementById('review-modal');
    if (event.target === modal) {
      closeReviewModal();
    }
  });

  // 사용자 반응 상태 로드
  loadUserReactions();
});

// 좋아요 토글
function toggleLike(stationId) {
  if (!isLoggedIn()) {
    alert('로그인이 필요합니다.');
    window.location.href = '../login.php';
    return;
  }

  const likeBtn = document.querySelector(`[data-station="${stationId}"].like-btn`);
  const dislikeBtn = document.querySelector(`[data-station="${stationId}"].dislike-btn`);
  const likeCountSpan = likeBtn.querySelector('.like-count');
  const dislikeCountSpan = dislikeBtn.querySelector('.dislike-count');

  let likeCount = parseInt(likeCountSpan.textContent);
  let dislikeCount = parseInt(dislikeCountSpan.textContent);

  // 현재 상태
  const wasLiked = likeBtn.classList.contains('active');
  const wasDisliked = dislikeBtn.classList.contains('active');

  if (wasLiked) {
    // 좋아요 취소
    likeBtn.classList.remove('active');
    likeCount--;
    userReactions[stationId] = null;
  } else {
    // 좋아요 추가
    likeBtn.classList.add('active');
    likeCount++;

    // 싫어요가 활성화되어 있다면 취소
    if (wasDisliked) {
      dislikeBtn.classList.remove('active');
      dislikeCount--;
    }

    userReactions[stationId] = 'like';
  }

  // UI 업데이트
  likeCountSpan.textContent = likeCount;
  dislikeCountSpan.textContent = dislikeCount;

  // 서버에 전송
  sendReaction(stationId, userReactions[stationId]);
}

// 싫어요 토글
function toggleDislike(stationId) {
  if (!isLoggedIn()) {
    alert('로그인이 필요합니다.');
    window.location.href = '../login.php';
    return;
  }

  const likeBtn = document.querySelector(`[data-station="${stationId}"].like-btn`);
  const dislikeBtn = document.querySelector(`[data-station="${stationId}"].dislike-btn`);
  const likeCountSpan = likeBtn.querySelector('.like-count');
  const dislikeCountSpan = dislikeBtn.querySelector('.dislike-count');

  let likeCount = parseInt(likeCountSpan.textContent);
  let dislikeCount = parseInt(dislikeCountSpan.textContent);

  // 현재 상태
  const wasLiked = likeBtn.classList.contains('active');
  const wasDisliked = dislikeBtn.classList.contains('active');

  if (wasDisliked) {
    // 싫어요 취소
    dislikeBtn.classList.remove('active');
    dislikeCount--;
    userReactions[stationId] = null;
  } else {
    // 싫어요 추가
    dislikeBtn.classList.add('active');
    dislikeCount++;

    // 좋아요가 활성화되어 있다면 취소
    if (wasLiked) {
      likeBtn.classList.remove('active');
      likeCount--;
    }

    userReactions[stationId] = 'dislike';
  }

  // UI 업데이트
  likeCountSpan.textContent = likeCount;
  dislikeCountSpan.textContent = dislikeCount;

  // 서버에 전송
  sendReaction(stationId, userReactions[stationId]);
}

// 한줄평 모달 표시
function showReviewModal(stationId, stationName) {
  if (!isLoggedIn()) {
    alert('로그인이 필요합니다.');
    window.location.href = '../login.php';
    return;
  }

  document.getElementById('review-station-id').value = stationId;
  document.getElementById('selected-station-name').textContent = stationName;
  document.getElementById('review-content').value = '';
  updateCharCount();

  // 한줄평 목록 로드
  loadReviews(stationId);

  document.getElementById('review-modal').style.display = 'block';
}

// 한줄평 목록 로드
function loadReviews(stationId, page = 1) {
  const reviewsList = document.getElementById('reviews-list');
  const loadMoreBtn = document.getElementById('load-more-reviews');

  if (page === 1) {
    reviewsList.innerHTML = '<div class="loading-reviews">💭 한줄평을 불러오는 중...</div>';
  }

  fetch(`../api/reviews.php?station_id=${stationId}&page=${page}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        if (page === 1) {
          displayReviews(data.reviews);
        } else {
          appendReviews(data.reviews);
        }

        // 더보기 버튼 표시/숨김
        if (data.has_more) {
          loadMoreBtn.style.display = 'block';
          loadMoreBtn.onclick = () => loadReviews(stationId, page + 1);
        } else {
          loadMoreBtn.style.display = 'none';
        }
      } else {
        if (page === 1) {
          reviewsList.innerHTML = '<div class="empty-reviews">📝 아직 한줄평이 없어요</div>';
        }
      }
    })
    .catch(error => {
      console.error('한줄평 로드 오류:', error);
      if (page === 1) {
        reviewsList.innerHTML = '<div class="empty-reviews">한줄평을 불러올 수 없습니다.</div>';
      }
    });
}

// 한줄평 목록 표시
function displayReviews(reviews) {
  const reviewsList = document.getElementById('reviews-list');

  if (reviews.length === 0) {
    reviewsList.innerHTML = '<div class="empty-reviews">아직 작성된 한줄평이 없습니다.</div>';
    return;
  }

  reviewsList.innerHTML = reviews.map(review => createReviewHTML(review)).join('');
}

// 한줄평 목록에 추가
function appendReviews(reviews) {
  const reviewsList = document.getElementById('reviews-list');
  const newReviewsHTML = reviews.map(review => createReviewHTML(review)).join('');
  reviewsList.insertAdjacentHTML('beforeend', newReviewsHTML);
}

// 한줄평 HTML 생성
function createReviewHTML(review) {
  const isMyReview = review.is_my_review || false;
  const reviewClass = isMyReview ? 'review-item my-review' : 'review-item';
  const ipAddress = review.ip_address || '알 수 없음';
  const deleteButton = isMyReview ?
                       `<button class="delete-review-btn" onclick="deleteReview(${review.id})">삭제</button>` : '';

  const reviewDate = new Date(review.created_at).toLocaleString('ko-KR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });

  return `
    <div class="${reviewClass}" data-review-id="${review.id}">
      <div class="review-header">
        <span class="review-author">${escapeHtml(review.username)}</span>
        <span class="review-date">${reviewDate}</span>
      </div>
      <div class="review-content">${escapeHtml(review.content)}</div>
      <div class="review-actions">
      ${deleteButton ? `<div class="review-actions">${deleteButton}</div>` : ''}
        <div class="review-ip">${ipAddress}</div>
      </div>
    </div>
  `;
}

// 한줄평 삭제
function deleteReview(reviewId) {
  if (!confirm('한줄평을 삭제하시겠습니까?')) {
    return;
  }

  fetch('../api/reviews.php', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ review_id: reviewId })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // DOM에서 해당 한줄평 제거
        const reviewElement = document.querySelector(`[data-review-id="${reviewId}"]`);
        if (reviewElement) {
          reviewElement.remove();
        }

        // 한줄평 카운트 업데이트
        const stationId = document.getElementById('review-station-id').value;
        updateReviewCount(stationId, -1);

        alert('한줄평이 삭제되었습니다.');
      } else {
        alert(data.message || '한줄평 삭제에 실패했습니다.');
      }
    })
    .catch(error => {
      console.error('한줄평 삭제 오류:', error);
      alert('한줄평 삭제 중 오류가 발생했습니다.');
    });
}

// 한줄평 카운트 업데이트
function updateReviewCount(stationId, change) {
  const reviewButtons = document.querySelectorAll('.review-btn');
  reviewButtons.forEach(btn => {
    if (btn.onclick.toString().includes(stationId)) {
      const countSpan = btn.querySelector('.review-count');
      if (countSpan) {
        const currentCount = parseInt(countSpan.textContent);
        countSpan.textContent = Math.max(0, currentCount + change);
      }
    }
  });
}

// 한줄평 모달 닫기
function closeReviewModal() {
  document.getElementById('review-modal').style.display = 'none';
}

// 글자 수 업데이트
function updateCharCount() {
  const textarea = document.getElementById('review-content');
  const charCount = document.querySelector('.char-count');
  const currentLength = textarea.value.length;
  charCount.textContent = `${currentLength}/100`;

  if (currentLength > 90) {
    charCount.style.color = '#dc3545';
  } else if (currentLength > 70) {
    charCount.style.color = '#ffc107';
  } else {
    charCount.style.color = '#666';
  }
}

// 한줄평 제출 처리
function handleReviewSubmit(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const reviewData = {
    station_id: formData.get('station_id'),
    content: formData.get('content').trim()
  };

  if (!reviewData.content) {
    alert('한줄평을 입력해주세요.');
    return;
  }

  // 서버에 전송
  fetch('../api/reviews.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(reviewData)
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('한줄평이 등록되었습니다!');

        // 폼 초기화
        document.getElementById('review-content').value = '';
        updateCharCount();

        // 한줄평 목록 새로고침
        loadReviews(reviewData.station_id);

        // 한줄평 카운트 업데이트
        updateReviewCount(reviewData.station_id, 1);

      } else {
        alert(data.message || '한줄평 등록에 실패했습니다.');
      }
    })
    .catch(error => {
      console.error('한줄평 등록 오류:', error);
      alert('한줄평 등록 중 오류가 발생했습니다.');
    });
}

// 반응 서버 전송
function sendReaction(stationId, reaction) {
  const reactionData = {
    station_id: stationId,
    reaction: reaction // 'like', 'dislike', null
  };

  fetch('../api/reactions.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(reactionData)
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        console.error('반응 저장 실패:', data.message);
        // 실패시 UI 롤백 로직 추가 가능
      }
    })
    .catch(error => {
      console.error('반응 전송 오류:', error);
    });
}

// 사용자 반응 상태 로드
function loadUserReactions() {
  if (!isLoggedIn()) {
    return;
  }

  fetch('../api/user-reactions.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        userReactions = data.reactions;

        // UI에 사용자 반응 상태 반영
        Object.keys(userReactions).forEach(stationId => {
          const reaction = userReactions[stationId];
          if (reaction === 'like') {
            document.querySelector(`[data-station="${stationId}"].like-btn`)?.classList.add('active');
          } else if (reaction === 'dislike') {
            document.querySelector(`[data-station="${stationId}"].dislike-btn`)?.classList.add('active');
          }
        });
      }
    })
    .catch(error => {
      console.error('사용자 반응 로드 오류:', error);
    });
}

// 로그인 상태 확인
function isLoggedIn() {
  // PHP 세션 정보를 JavaScript로 전달받아 확인
  return window.userLoggedIn || false;
}

// 유틸리티 함수
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}