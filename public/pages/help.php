<?php
session_start();
$page_title = "도움말";
include_once '../includes/header.php';
?>

    <main class="help-page">
        <div class="container">
            <div class="page-header">
                <h1>도움말</h1>
                <p>EGCharge 이용에 관한 궁금한 점을 해결해보세요</p>
            </div>

            <!-- 검색 섹션 -->
            <div class="help-search">
                <input type="text" id="help-search" placeholder="궁금한 내용을 검색하세요">
                <button class="btn btn-primary">검색</button>
            </div>

            <!-- 카테고리별 도움말 -->
            <div class="help-categories">
                <div class="category-card">
                    <div class="category-icon">🚗</div>
                    <h3>시작하기</h3>
                    <ul>
                        <li><a href="#guide-signup">회원가입 방법</a></li>
                        <li><a href="#guide-first">첫 예약하기</a></li>
                        <li><a href="#guide-app">앱 사용법</a></li>
                    </ul>
                </div>

                <div class="category-card">
                    <div class="category-icon">🔍</div>
                    <h3>충전소 찾기</h3>
                    <ul>
                        <li><a href="#guide-search">충전소 검색</a></li>
                        <li><a href="#guide-filter">필터 사용법</a></li>
                        <li><a href="#guide-map">지도 보기</a></li>
                    </ul>
                </div>

                <div class="category-card">
                    <div class="category-icon">📅</div>
                    <h3>예약 관리</h3>
                    <ul>
                        <li><a href="#guide-booking">예약하기</a></li>
                        <li><a href="#guide-cancel">예약 취소</a></li>
                        <li><a href="#guide-extend">시간 연장</a></li>
                    </ul>
                </div>

                <div class="category-card">
                    <div class="category-icon">💳</div>
                    <h3>결제 및 요금</h3>
                    <ul>
                        <li><a href="#guide-payment">결제 방법</a></li>
                        <li><a href="#guide-price">요금 안내</a></li>
                        <li><a href="#guide-receipt">영수증 발급</a></li>
                    </ul>
                </div>
            </div>

            <!-- 자주 묻는 질문 -->
            <div id="faq-section" class="faq-section">
                <h2>자주 묻는 질문</h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>예약 없이 충전소를 이용할 수 있나요?</span>
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            아니요. 원활한 서비스 이용을 위해 반드시 사전 예약이 필요합니다.
                            앱이나 웹사이트에서 간편하게 예약하실 수 있습니다.
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>예약 시간을 초과하면 어떻게 되나요?</span>
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            예약 시간 초과 시 10분 단위로 추가 요금이 부과됩니다.
                            미리 시간 연장 기능을 이용하시거나 충전이 완료되면 즉시 완료 처리해주세요.
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>충전기 고장 시 어떻게 해야 하나요?</span>
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            고객센터(1588-0000)로 즉시 연락주시거나 앱 내 신고 기능을 이용해주세요.
                            확인 후 다른 충전소로 변경 안내해드리겠습니다.
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>환불은 언제까지 가능한가요?</span>
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            예약 시작 1시간 전까지 무료 취소 가능합니다.
                            그 이후에는 취소 수수료가 부과될 수 있습니다.
                        </div>
                    </div>
                </div>
            </div>

            <!-- 연락처 정보 -->
            <div id="contact-section" class="contact-section">
                <h2>추가 도움이 필요하신가요?</h2>
                <div class="contact-options">
                    <div class="contact-card">
                        <div class="contact-icon">📞</div>
                        <h4>전화 문의</h4>
                        <p>준비중</p>
                        <p>평일 09:00-18:00</p>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">✉️</div>
                        <h4>이메일 문의</h4>
                        <p>woridori@gmail.com</p>
                        <p>24시간 접수 가능</p>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">💬</div>
                        <h4>카카오 1:1 채팅</h4>
                        <p>실시간 상담</p>
                        <button class="btn btn-outline" onclick="openChat()">채팅 시작</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <style>
      .help-page {
        padding: 2rem 0;
      }

      .help-search {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 3rem;
        display: flex;
        gap: 1rem;
      }

      .help-search input {
        flex: 1;
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 5px;
        font-size: 1rem;
      }

      .help-categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
      }

      .category-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
      }

      .category-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
      }

      .category-card h3 {
        margin-bottom: 1rem;
        color: #2c3e50;
      }

      .category-card ul {
        list-style: none;
        text-align: left;
      }

      .category-card li {
        margin-bottom: 0.5rem;
      }

      .category-card a {
        color: #3498db;
        text-decoration: none;
        padding: 0.5rem;
        display: block;
        border-radius: 5px;
        transition: background-color 0.3s;
      }

      .category-card a:hover {
        background: #f8f9fa;
      }

      .faq-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 3rem;
      }

      .faq-section h2 {
        margin-bottom: 2rem;
        color: #2c3e50;
        text-align: center;
      }

      .faq-item {
        border-bottom: 1px solid #eee;
        margin-bottom: 1rem;
      }

      .faq-question {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        cursor: pointer;
        font-weight: 600;
        color: #2c3e50;
      }

      .faq-toggle {
        font-size: 1.5rem;
        color: #3498db;
        transition: transform 0.3s;
      }

      .faq-question.active .faq-toggle {
        transform: rotate(45deg);
      }

      .faq-answer {
        padding: 0 0 1rem 0;
        color: #666;
        line-height: 1.6;
        display: none;
      }

      .faq-answer.show {
        display: block;
      }

      .contact-section {
        text-align: center;
      }

      .contact-section h2 {
        margin-bottom: 2rem;
        color: #2c3e50;
      }

      .contact-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
      }

      .contact-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
      }

      .contact-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
      }

      .contact-card h4 {
        margin-bottom: 1rem;
        color: #2c3e50;
      }

      .contact-card p {
        margin-bottom: 0.5rem;
        color: #666;
      }

      /* 반응형 */
      @media (max-width: 768px) {
        .help-search {
          flex-direction: column;
        }

        .help-categories,
        .contact-options {
          grid-template-columns: 1fr;
        }
      }
    </style>

    <script>
      // FAQ 토글 기능
      function toggleFaq(element) {
        const answer = element.nextElementSibling;
        const isActive = element.classList.contains('active');

        // 모든 FAQ 닫기
        document.querySelectorAll('.faq-question').forEach(q => {
          q.classList.remove('active');
          q.nextElementSibling.classList.remove('show');
        });

        // 클릭한 FAQ만 열기 (닫혀있던 경우)
        if (!isActive) {
          element.classList.add('active');
          answer.classList.add('show');
        }
      }

      // 검색 기능
      document.getElementById('help-search').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const faqItems = document.querySelectorAll('.faq-item');
        const categoryCards = document.querySelectorAll('.category-card');

        // FAQ 검색
        faqItems.forEach(item => {
          const question = item.querySelector('.faq-question span').textContent.toLowerCase();
          const answer = item.querySelector('.faq-answer').textContent.toLowerCase();

          if (question.includes(query) || answer.includes(query)) {
            item.style.display = 'block';
          } else {
            item.style.display = query ? 'none' : 'block';
          }
        });

        // 카테고리 검색
        categoryCards.forEach(card => {
          const title = card.querySelector('h3').textContent.toLowerCase();
          const links = Array.from(card.querySelectorAll('a')).map(a => a.textContent.toLowerCase());

          if (title.includes(query) || links.some(link => link.includes(query))) {
            card.style.display = 'block';
          } else {
            card.style.display = query ? 'none' : 'block';
          }
        });
      });

      // 1:1 채팅 시작
      function openChat() {
        // 실제로는 채팅 시스템 연동
        alert('실시간 채팅 서비스는 준비 중입니다. 전화나 이메일로 문의해주세요.');
      }

      // 앵커 링크 부드러운 스크롤
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth'
            });
          }
        });
      });
    </script>

<?php include_once '../includes/footer.php'; ?>