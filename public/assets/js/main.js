document.addEventListener('DOMContentLoaded', function() {
  // 충전소 검색 기능
  const searchInput = document.getElementById('station-search');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value.trim();
      if (query.length >= 2) {
        searchStations(query);
      } else {
        clearSearchResults();
      }
    });
  }

  // 예약 카드 클릭 이벤트
  const bookingCards = document.querySelectorAll('.booking-card');
  bookingCards.forEach(card => {
    card.addEventListener('click', function() {
      const bookingId = this.dataset.bookingId;
      if (bookingId) {
        showBookingDetails(bookingId);
      }
    });
  });

  // 폼 검증
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      if (!validateForm(this)) {
        e.preventDefault();
      }
    });
  });
});

function searchStationBtn() {
  filterStations(); // 기존 filterStations 함수 호출
}
// 충전소 검색 함수
function searchStations(query) {
  const loadingElement = document.getElementById('search-loading');
  const resultsContainer = document.getElementById('search-results');

  if (loadingElement) loadingElement.style.display = 'block';

  fetch(`/api/stations.php?search=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
      if (loadingElement) loadingElement.style.display = 'none';

      if (data.success) {
        displaySearchResults(data.stations);
      } else {
        showErrorMessage(data.message || '검색 중 오류가 발생했습니다.');
      }
    })
    .catch(error => {
      if (loadingElement) loadingElement.style.display = 'none';
      console.error('검색 오류:', error);
      showErrorMessage('검색 중 오류가 발생했습니다.');
    });
}

// 검색 결과 표시
function displaySearchResults(stations) {
  const resultsContainer = document.getElementById('search-results');
  if (!resultsContainer) return;

  resultsContainer.innerHTML = '';

  if (stations.length === 0) {
    resultsContainer.innerHTML = '<p class="empty-message">검색 결과가 없습니다.</p>';
    return;
  }

  stations.forEach(station => {
    const stationElement = createStationElement(station);
    resultsContainer.appendChild(stationElement);
  });
}

// 충전소 요소 생성
function createStationElement(station) {
  const div = document.createElement('div');
  div.className = 'station-card';
  div.innerHTML = `
        <div class="station-info">
            <h3>${escapeHtml(station.name)}</h3>
            <p class="station-address">${escapeHtml(station.address)}</p>
            <p class="station-price">가격: ${station.price}원/시간</p>
            <div class="station-status">
                <span class="status-badge ${station.status === 'active' ? 'status-active' : 'status-inactive'}">
                    ${station.status === 'active' ? '이용가능' : '이용불가'}
                </span>
            </div>
        </div>
        <div class="station-actions">
            ${station.status === 'active' ?
              `<button class="btn btn-primary" onclick="bookStation(${station.id})">예약하기</button>` :
              `<button class="btn btn-secondary" disabled>이용불가</button>`
  }
        </div>
    `;
  return div;
}

// 충전소 예약
function bookStation(stationId) {
  if (!confirm('이 충전소를 예약하시겠습니까?')) return;

  const bookingData = {
    station_id: stationId,
    action: 'create'
  };

  fetch('/api/bookings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(bookingData)
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showSuccessMessage('예약이 완료되었습니다.');
        setTimeout(() => {
          window.location.href = '/pages/bookings.php';
        }, 1500);
      } else {
        showErrorMessage(data.message || '예약에 실패했습니다.');
      }
    })
    .catch(error => {
      console.error('예약 오류:', error);
      showErrorMessage('예약 중 오류가 발생했습니다.');
    });
}

// 예약 취소
function cancelBooking(bookingId) {
  if (!confirm('예약을 취소하시겠습니까?')) return;

  fetch('/api/bookings.php', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      booking_id: bookingId,
      action: 'cancel'
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showSuccessMessage('예약이 취소되었습니다.');
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        showErrorMessage(data.message || '취소에 실패했습니다.');
      }
    })
    .catch(error => {
      console.error('취소 오류:', error);
      showErrorMessage('취소 중 오류가 발생했습니다.');
    });
}

// 폼 검증
function validateForm(form) {
  const requiredFields = form.querySelectorAll('[required]');
  let isValid = true;

  requiredFields.forEach(field => {
    if (!field.value.trim()) {
      showFieldError(field, '이 필드는 필수입니다.');
      isValid = false;
    } else {
      clearFieldError(field);
    }
  });

  // 비밀번호 확인
  const password = form.querySelector('input[name="password"]');
  const confirmPassword = form.querySelector('input[name="confirm_password"]');

  if (password && confirmPassword) {
    if (password.value !== confirmPassword.value) {
      showFieldError(confirmPassword, '비밀번호가 일치하지 않습니다.');
      isValid = false;
    }
  }

  // 이메일 형식 검증
  const emailField = form.querySelector('input[type="email"]');
  if (emailField && !isValidEmail(emailField.value)) {
    showFieldError(emailField, '올바른 이메일 형식이 아닙니다.');
    isValid = false;
  }

  return isValid;
}

// 이메일 형식 검증
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// 필드 에러 표시
function showFieldError(field, message) {
  clearFieldError(field);

  const errorDiv = document.createElement('div');
  errorDiv.className = 'field-error';
  errorDiv.textContent = message;

  field.parentNode.appendChild(errorDiv);
  field.classList.add('error');
}

// 필드 에러 제거
function clearFieldError(field) {
  const existingError = field.parentNode.querySelector('.field-error');
  if (existingError) {
    existingError.remove();
  }
  field.classList.remove('error');
}

// 성공 메시지 표시
function showSuccessMessage(message) {
  showMessage(message, 'success');
}

// 에러 메시지 표시
function showErrorMessage(message) {
  showMessage(message, 'error');
}

// 메시지 표시 (토스트)
function showMessage(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;

  document.body.appendChild(toast);

  // 애니메이션
  setTimeout(() => toast.classList.add('show'), 100);

  // 자동 제거
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// 검색 결과 초기화
function clearSearchResults() {
  const resultsContainer = document.getElementById('search-results');
  if (resultsContainer) {
    resultsContainer.innerHTML = '';
  }
}

// HTML 이스케이프
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// 디바운스 함수
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

// 로딩 인디케이터 표시
function showLoading(element) {
  if (element) {
    element.innerHTML = '<div class="loading">로딩중...</div>';
  }
}

// 로딩 인디케이터 숨김
function hideLoading(element) {
  if (element) {
    const loading = element.querySelector('.loading');
    if (loading) loading.remove();
  }
}

// 기존 main.js 코드에 추가

// 페이지 체류시간 추적
let pageStartTime = Date.now();
let visitLogId = window.visitLogId || null;

// 페이지 이탈 시 체류시간 업데이트
window.addEventListener('beforeunload', function() {
  if (visitLogId) {
    const duration = Math.round((Date.now() - pageStartTime) / 1000);

    // 체류시간 서버로 전송 (sendBeacon 사용)
    const data = JSON.stringify({
      log_id: visitLogId,
      duration: duration
    });

    navigator.sendBeacon('../api/update-visit-duration.php', data);
  }
});

// 페이지 가시성 API로 정확한 체류시간 측정
document.addEventListener('visibilitychange', function() {
  if (document.visibilityState === 'hidden') {
    // 페이지가 숨겨질 때
    if (visitLogId) {
      const duration = Math.round((Date.now() - pageStartTime) / 1000);

      fetch('../api/update-visit-duration.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          log_id: visitLogId,
          duration: duration
        }),
        keepalive: true
      }).catch(() => {
        // 실패해도 무시
      });
    }
  } else if (document.visibilityState === 'visible') {
    // 페이지가 다시 보일 때 시작시간 리셋
    pageStartTime = Date.now();
  }
});