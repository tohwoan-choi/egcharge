document.addEventListener('DOMContentLoaded', function() {
  // 충전소 검색 기능
  const searchInput = document.getElementById('station-search');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      searchStations(this.value);
    });
  }

  // 예약 상태 업데이트
  const bookingCards = document.querySelectorAll('.booking-card');
  bookingCards.forEach(card => {
    card.addEventListener('click', function() {
      showBookingDetails(this.dataset.bookingId);
    });
  });
});

function searchStations(query) {
  if (query.length < 2) return;

  fetch(`/api/stations.php?search=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
      displaySearchResults(data);
    })
    .catch(error => {
      console.error('검색 오류:', error);
    });
}

function displaySearchResults(stations) {
  const resultsContainer = document.getElementById('search-results');
  if (!resultsContainer) return;

  resultsContainer.innerHTML = '';

  stations.forEach(station => {
    const stationElement = createStationElement(station);
    resultsContainer.appendChild(stationElement);
  });
}

function createStationElement(station) {
  const div = document.createElement('div');
  div.className = 'station-card';
  div.innerHTML = `
        <h3>${station.name}</h3>
        <p>${station.address}</p>
        <p>가격: ${station.price}원/시간</p>
        <button class="btn btn-primary" onclick="bookStation(${station.id})">예약하기</button>
    `;
  return div;
}

function bookStation(stationId) {
  if (!confirm('이 충전소를 예약하시겠습니까?')) return;

  fetch('/api/bookings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      station_id: stationId,
      action: 'create'
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('예약이 완료되었습니다.');
        location.reload();
      } else {
        alert('예약에 실패했습니다: ' + data.message);
      }
    })
    .catch(error => {
      console.error('예약 오류:', error);
      alert('예약 중 오류가 발생했습니다.');
    });
}