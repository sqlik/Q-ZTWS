/**
 * main.js
 * Główny plik JavaScript dla aplikacji Q-ZTWS
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja tooltipów Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicjalizacja popoverów Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Obsługa zamykania alertów
    document.querySelectorAll('.alert .btn-close').forEach(function(button) {
        button.addEventListener('click', function() {
            this.parentNode.classList.add('fade');
            setTimeout(() => {
                this.parentNode.remove();
            }, 150);
        });
    });
    
    // Automatyczne ukrywanie alertów po 5 sekundach
    setTimeout(function() {
        document.querySelectorAll('.alert:not(.alert-important)').forEach(function(alert) {
            if (alert) {
                alert.classList.add('fade');
                setTimeout(() => {
                    alert.remove();
                }, 150);
            }
        });
    }, 5000);
    
    // Obsługa formularzy z walidacją
    document.querySelectorAll('form.needs-validation').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Obsługa konwersji kodu dostępu na wielkie litery
    document.querySelectorAll('input[name="access_code"]').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });
    
    // Obsługa licznika czasu dla uczestnika quizu
    const timerElement = document.getElementById('questionTimer');
    if (timerElement) {
        const timeLimit = parseInt(timerElement.dataset.timeLimit, 10);
        const startTime = new Date().getTime();
        let timeLeft = timeLimit;
        
        // Pole ukryte do przechowywania czasu odpowiedzi
        const responseTimeInput = document.getElementById('response_time');
        
        const timer = setInterval(function() {
            const currentTime = new Date().getTime();
            const elapsedTime = Math.floor((currentTime - startTime) / 1000);
            timeLeft = timeLimit - elapsedTime;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                timeLeft = 0;
                timerElement.style.width = '0%';
                
                // Automatyczne wysłanie formularza po upływie czasu
                const answerForm = document.getElementById('answerForm');
                if (answerForm) {
                    // Wybierz domyślną odpowiedź, jeśli żadna nie została wybrana
                    const checkedInputs = answerForm.querySelectorAll('input[name="answers[]"]:checked, input[name="answers"]:checked');
                    if (checkedInputs.length === 0) {
                        const firstInput = answerForm.querySelector('input[name="answers[]"], input[name="answers"]');
                        if (firstInput) {
                            firstInput.checked = true;
                        }
                    }
                    
                    // Ustaw czas odpowiedzi na maksymalny
                    if (responseTimeInput) {
                        responseTimeInput.value = timeLimit;
                    }
                    
                    answerForm.submit();
                }
            } else {
                const percentage = (timeLeft / timeLimit) * 100;
                timerElement.style.width = percentage + '%';
                
                // Zmień kolor paska w zależności od pozostałego czasu
                if (percentage > 66) {
                    timerElement.classList.remove('bg-warning', 'bg-danger');
                    timerElement.classList.add('bg-success');
                } else if (percentage > 33) {
                    timerElement.classList.remove('bg-success', 'bg-danger');
                    timerElement.classList.add('bg-warning');
                } else {
                    timerElement.classList.remove('bg-success', 'bg-warning');
                    timerElement.classList.add('bg-danger');
                }
                
                // Aktualizuj czas odpowiedzi w ukrytym polu
                if (responseTimeInput) {
                    responseTimeInput.value = elapsedTime;
                }
            }
        }, 100);
        
        // Obsługa wysłania formularza odpowiedzi
        const answerForm = document.getElementById('answerForm');
        if (answerForm) {
            answerForm.addEventListener('submit', function() {
                clearInterval(timer);
                
                // Zapisz czas odpowiedzi
                if (responseTimeInput) {
                    const currentTime = new Date().getTime();
                    const elapsedTime = (currentTime - startTime) / 1000;
                    responseTimeInput.value = Math.min(elapsedTime, timeLimit);
                }
            });
        }
    }
    
    // Obsługa Socket.io dla hostowania quizu i uczestnictwa
    const sessionId = document.getElementById('sessionId')?.value;
    if (sessionId && typeof io !== 'undefined') {
        // Podłącz do kanału sesji
        const socket = io.connect('/session/' + sessionId);
        
        // Obsługa zdarzeń dla prowadzącego quiz
        if (document.getElementById('hostPanel')) {
            // Zdarzenie dołączenia uczestnika
            socket.on('participant_joined', function(data) {
                const participantsList = document.getElementById('participantsList');
                if (participantsList) {
                    const participantItem = document.createElement('div');
                    participantItem.className = 'participant-item';
                    participantItem.dataset.participantId = data.participant_id;
                    participantItem.innerHTML = `
                        <span class="participant-name">${data.nickname}</span>
                        <span class="participant-score">0</span>
                    `;
                    participantsList.appendChild(participantItem);
                    
                    // Aktualizuj licznik uczestników
                    const participantsCount = document.getElementById('participantsCount');
                    if (participantsCount) {
                        const count = parseInt(participantsCount.textContent, 10) + 1;
                        participantsCount.textContent = count;
                    }
                }
                
                // Pokaż powiadomienie
                showToast(`${data.nickname} dołączył do quizu!`);
            });
            
            // Zdarzenie odpowiedzi uczestnika
            socket.on('participant_answered', function(data) {
                const participantItem = document.querySelector(`.participant-item[data-participant-id="${data.participant_id}"]`);
                if (participantItem) {
                    const scoreSpan = participantItem.querySelector('.participant-score');
                    const currentScore = parseInt(scoreSpan.textContent, 10);
                    const newScore = currentScore + data.score;
                    scoreSpan.textContent = newScore;
                    
                    // Dodaj klasę "answered" do uczestnika
                    participantItem.classList.add('answered');
                    
                    // Aktualizuj licznik odpowiedzi
                    const answersCount = document.getElementById('answersCount');
                    if (answersCount) {
                        const count = parseInt(answersCount.textContent, 10) + 1;
                        answersCount.textContent = count;
                    }
                }
                
                // Pokaż powiadomienie
                showToast(`${data.nickname} odpowiedział!`);
            });
        }
        
        // Obsługa zdarzeń dla uczestnika quizu
        if (document.getElementById('participantPanel')) {
            // Zdarzenie zmiany pytania
            socket.on('question_changed', function(data) {
                // Przeładuj stronę, aby zobaczyć nowe pytanie
                window.location.reload();
            });
            
            // Zdarzenie pokazania odpowiedzi
            socket.on('show_answers', function(data) {
                // Przeładuj stronę, aby zobaczyć odpowiedzi
                window.location.reload();
            });
            
            // Zdarzenie zakończenia sesji
            socket.on('session_ended', function(data) {
                // Przekieruj do wyników
                window.location.href = `/quiz/${sessionId}/results`;
            });
        }
    }
});

/**
 * Pokazuje powiadomienie typu toast
 * 
 * @param {string} message Treść powiadomienia
 * @param {string} type Typ powiadomienia (success, warning, danger, info)
 */
function showToast(message, type = 'info') {
    const toastsContainer = document.getElementById('toastsContainer');
    if (!toastsContainer) {
        const container = document.createElement('div');
        container.id = 'toastsContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = 1050;
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.ariaLive = 'assertive';
    toast.ariaAtomic = 'true';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    const toastsContainer = document.getElementById('toastsContainer');
    toastsContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
}
