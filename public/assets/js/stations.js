// stations.js

let userReactions = {};
let currentStations = [];

document.addEventListener('DOMContentLoaded', function() {
  // 페이지 로드시 초기 데이터 로드
  loadStations(window.initialParams || {});


  const searchForm = document.getElementById('search-filter-form');
  if (searchForm) {
    // 폼 제출 이벤트
    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      applyFilters();
    });
  }

  // 검색 입력 필드 실시간 검색 (디바운스 적용)
  const searchInput = document.getElementById('station-search');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(function() {
      applyFilters();
    }, 500)); // 500ms 대기 후 검색
  }
  // 필터 셀렉트 변경 시 즉시 적용
  const filterSelects = ['charge-type-filter', 'connector-filter', 'status-filter'];
  filterSelects.forEach(selectId => {
    const selectElement = document.getElementById(selectId);
    if (selectElement) {
      selectElement.addEventListener('change', applyFilters);
    }
  });

  // 폼 이벤트 리스너
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

  loadUserReactions();
});

// 충전소 데이터 로드
function loadStations(params = {}) {
  const loading = document.getElementById('loading');
  const stationsGrid = document.getElementById('stations-grid');

  loading.style.display = 'block';
  stationsGrid.innerHTML = '';

  // API 파라미터 구성
  const urlParams = new URLSearchParams();
  Object.keys(params).forEach(key => {
    if (params[key]) {
      urlParams.append(key, params[key]);
    }
  });

  fetch(`../api/stations.php?${urlParams}`)
    .then(response => response.json())
    .then(data => {
      loading.style.display = 'none';

      if (data.success && data.stations) {
        currentStations = data.stations;
        displayStations(data.stations);
        loadStationReactions();
      } else {
        stationsGrid.innerHTML = '<p class="empty-message">검색 결과가 없습니다.</p>';
      }
    })
    .catch(error => {
      loading.style.display = 'none';
      console.error('충전소 로드 오류:', error);
      stationsGrid.innerHTML = '<p class="error-message">충전소 정보를 불러올 수 없습니다.</p>';
    });
}

// 충전소 목록 표시
function displayStations(stations) {
  const stationsGrid = document.getElementById('stations-grid');

  if (!stations || stations.length === 0) {
    stationsGrid.innerHTML = '<p class="empty-message">검색 결과가 없습니다.</p>';
    return;
  }

  // 충전소별로 그룹화
  const stationGroups = {};
  stations.forEach(station => {
    const key = station.csId;
    if (!stationGroups[key]) {
      stationGroups[key] = {
        csNm: station.csNm,
        addr: station.addr,
        lat: station.lat,
        lngi: station.lngi,
        statUpdatetime: station.statUpdatetime,
        chargers: []
      };
    }
    stationGroups[key].chargers.push(station);
  });

  // HTML 생성
  const html = Object.keys(stationGroups).map(csId => {
    const group = stationGroups[csId];
    const availableChargers = group.chargers.filter(c => c.cpStat == 1);
    const chargingChargers = group.chargers.filter(c => c.cpStat == 2);
    const brokenChargers = group.chargers.filter(c => c.cpStat == 3);

    return createStationCardHTML(csId, group, availableChargers, chargingChargers, brokenChargers);
  }).join('');

  stationsGrid.innerHTML = html;
}

// 충전소 카드 HTML 생성
function createStationCardHTML(csId, group, availableChargers, chargingChargers, brokenChargers) {
  const chargersHTML = group.chargers.map(charger => `
        <div class="charger-details">
            <h4>${escapeHtml(charger.cpNm)}</h4>
            <div class="detail-tags">
                <span>${escapeHtml(charger.charegTpNm)}</span>
                <span>${escapeHtml(charger.cpTpNm)}</span>
                <span class="${getStatusClass(charger.cpStat)}">
                    ${escapeHtml(charger.cpStatNm)}
                </span>
            </div>
        </div>
    `).join('');

  return `
        <div class="station-card">
            <div class="station-header">
                <div class="station-info">
                    <h3>${escapeHtml(group.csNm)}</h3>
                    <p class="station-address">${escapeHtml(group.addr)}</p>
                </div>
                <div class="station-summary">
                    <small>
                        충전가능: ${availableChargers.length} |
                        충전중: ${chargingChargers.length} |
                        고장: ${brokenChargers.length}
                    </small>
                </div>
            </div>
            
            <div class="station-reactions">
                <div class="reaction-buttons">
                    <button class="reaction-btn like-btn" onclick="toggleLike('${csId}')" data-station="${csId}">
                        👍 <span class="like-count">0</span>
                    </button>
                    <button class="reaction-btn dislike-btn" onclick="toggleDislike('${csId}')" data-station="${csId}">
                        👎 <span class="dislike-count">0</span>
                    </button>
                    <button class="reaction-btn review-btn" onclick="showReviewModal('${csId}', '${escapeHtml(group.csNm)}')">
                        💬 <span class="review-count">0</span>
                    </button>
                </div>
                <p class="update-time">업데이트: ${escapeHtml(group.statUpdatetime)}</p>
            </div>
            
            <div class="chargers-list">
                ${chargersHTML}
            </div>
        </div>
    `;
}

// 충전소별 반응 데이터 로드
function loadStationReactions() {
  const stationIds = [...new Set(currentStations.map(s => s.csId))];

  if (stationIds.length === 0) return;

  fetch('../api/station-reactions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ station_ids: stationIds })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.reactions) {
        updateReactionCounts(data.reactions);
      }
    })
    .catch(error => {
      console.error('반응 데이터 로드 오류:', error);
    });
}

// 반응 카운트 업데이트
function updateReactionCounts(reactions) {
  Object.keys(reactions).forEach(stationId => {
    const reaction = reactions[stationId];
    const likeBtn = document.querySelector(`[data-station="${stationId}"].like-btn`);
    const dislikeBtn = document.querySelector(`[data-station="${stationId}"].dislike-btn`);
    const reviewBtn = document.querySelector(`.review-btn[onclick*="${stationId}"]`);

    if (likeBtn) {
      likeBtn.querySelector('.like-count').textContent = reaction.likes || 0;
    }
    if (dislikeBtn) {
      dislikeBtn.querySelector('.dislike-count').textContent = reaction.dislikes || 0;
    }
    if (reviewBtn) {
      reviewBtn.querySelector('.review-count').textContent = reaction.reviews_count || 0;
    }
  });
}

// 필터 적용
function applyFilters() {
  const form = document.getElementById('search-filter-form');
  const formData = new FormData(form);

  const params = {
    search: formData.get('search') || '',
    charge_type: formData.get('charge_type') || '',
    connector_type: formData.get('connector_type') || '',
    status: formData.get('status') || ''
  };

  // URL 업데이트
  const url = new URL(window.location);
  Object.keys(params).forEach(key => {
    if (params[key]) {
      url.searchParams.set(key, params[key]);
    } else {
      url.searchParams.delete(key);
    }
  });
  window.history.pushState({}, '', url);

  loadStations(params);
}

// 상태 클래스 반환
function getStatusClass(status) {
  switch(parseInt(status)) {
    case 1: return 'status-available';
    case 2: return 'status-charging';
    case 3: return 'status-broken';
    case 4:
    case 5: return 'status-offline';
    default: return '';
  }
}

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

  const wasLiked = likeBtn.classList.contains('active');
  const wasDisliked = dislikeBtn.classList.contains('active');

  if (wasLiked) {
    likeBtn.classList.remove('active');
    likeCount--;
    userReactions[stationId] = null;
  } else {
    likeBtn.classList.add('active');
    likeCount++;

    if (wasDisliked) {
      dislikeBtn.classList.remove('active');
      dislikeCount--;
    }

    userReactions[stationId] = 'like';
  }

  likeCountSpan.textContent = likeCount;
  dislikeCountSpan.textContent = dislikeCount;

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

  const wasLiked = likeBtn.classList.contains('active');
  const wasDisliked = dislikeBtn.classList.contains('active');

  if (wasDisliked) {
    dislikeBtn.classList.remove('active');
    dislikeCount--;
    userReactions[stationId] = null;
  } else {
    dislikeBtn.classList.add('active');
    dislikeCount++;

    if (wasLiked) {
      likeBtn.classList.remove('active');
      likeCount--;
    }

    userReactions[stationId] = 'dislike';
  }

  likeCountSpan.textContent = likeCount;
  dislikeCountSpan.textContent = dislikeCount;

  sendReaction(stationId, userReactions[stationId]);
}

// 한줄평 모달 표시
function showReviewModal(stationId, stationName) {
  document.getElementById('review-station-id').value = stationId;
  document.getElementById('selected-station-name').textContent = stationName;
  document.getElementById('review-content').value = '';
  updateCharCount();

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
                <span class="review-author">${escapeHtml(review.name)}</span>
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
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ review_id: reviewId })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const reviewElement = document.querySelector(`[data-review-id="${reviewId}"]`);
        if (reviewElement) {
          reviewElement.remove();
        }

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

  fetch('../api/reviews.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(reviewData)
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('한줄평이 등록되었습니다!');

        document.getElementById('review-content').value = '';
        updateCharCount();

        loadReviews(reviewData.station_id);
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
    reaction: reaction
  };

  fetch('../api/reactions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(reactionData)
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        console.error('반응 저장 실패:', data.message);
      }
    })
    .catch(error => {
      console.error('반응 전송 오류:', error);
    });
}

// 사용자 반응 상태 로드
function loadUserReactions() {
  if (!isLoggedIn()) return;

  fetch('../api/user-reactions.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        userReactions = data.reactions;

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