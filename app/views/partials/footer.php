<?php
/**
 * app/views/partials/footer.php
 * Stopka strony dla aplikacji Q-ZTWS
 */
?>
    </main>
    
    <!-- Stopka strony -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted">
                        &copy; <?= date('Y') ?> <?= APP_NAME ?> | <?= __('all_rights_reserved') ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?= APP_URL ?>/privacy-policy" class="text-muted me-3"><?= __('privacy_policy') ?></a>
                    <a href="<?= APP_URL ?>/terms-of-service" class="text-muted"><?= __('terms_of_service') ?></a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Kontener na powiadomienia toast -->
    <div id="toastsContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;"></div>
    
    <!-- Skrypty JavaScript -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap Bundle JS (zawiera Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <!-- SortableJS - biblioteka do przeciągania i upuszczania elementów -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    
    <!-- Własne skrypty -->
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
    
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <!-- Dodatkowe skrypty specyficzne dla strony -->
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= APP_URL ?>/assets/js/<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Skrypt do śledzenia czasu odpowiedzi w quizach -->
    <?php if (isset($isQuizPage) && $isQuizPage): ?>
    <script>
        // Inicjalizacja timera quiz
        $(document).ready(function() {
            // Jeśli istnieje element timera
            if ($('#questionTimer').length) {
                const timeLimit = parseInt($('#questionTimer').data('time-limit'), 10);
                const startTime = new Date().getTime();
                let timeLeft = timeLimit;
                
                // Pole ukryte do przechowywania czasu odpowiedzi
                const responseTimeInput = $('#response_time');
                
                const timer = setInterval(function() {
                    const currentTime = new Date().getTime();
                    const elapsedTime = Math.floor((currentTime - startTime) / 1000);
                    timeLeft = timeLimit - elapsedTime;
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        timeLeft = 0;
                        $('#questionTimer').css('width', '0%');
                        
                        // Automatyczne wysłanie formularza po upływie czasu
                        if ($('#answerForm').length) {
                            // Ustaw czas odpowiedzi na maksymalny
                            if (responseTimeInput.length) {
                                responseTimeInput.val(timeLimit);
                            }
                            
                            $('#answerForm').submit();
                        }
                    } else {
                        const percentage = (timeLeft / timeLimit) * 100;
                        $('#questionTimer').css('width', percentage + '%');
                        
                        // Zmień kolor paska w zależności od pozostałego czasu
                        if (percentage > 66) {
                            $('#questionTimer').removeClass('bg-warning bg-danger').addClass('bg-success');
                        } else if (percentage > 33) {
                            $('#questionTimer').removeClass('bg-success bg-danger').addClass('bg-warning');
                        } else {
                            $('#questionTimer').removeClass('bg-success bg-warning').addClass('bg-danger');
                        }
                        
                        // Aktualizuj czas odpowiedzi w ukrytym polu
                        if (responseTimeInput.length) {
                            responseTimeInput.val(elapsedTime);
                        }
                    }
                }, 100);
                
                // Obsługa wysłania formularza odpowiedzi
                $('#answerForm').on('submit', function() {
                    clearInterval(timer);
                    
                    // Zapisz czas odpowiedzi
                    if (responseTimeInput.length) {
                        const currentTime = new Date().getTime();
                        const elapsedTime = (currentTime - startTime) / 1000;
                        responseTimeInput.val(Math.min(elapsedTime, timeLimit));
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
    
    <!-- Skrypt dla Socket.IO, jeśli potrzebny -->
    <?php if (isset($useSocketIO) && $useSocketIO): ?>
    <script src="https://cdn.socket.io/4.6.0/socket.io.min.js"></script>
    <script>
        $(document).ready(function() {
            const sessionId = $('#sessionId').val();
            if (sessionId) {
                // Konfiguracja Socket.IO
                const socket = io.connect('/session/' + sessionId);
                
                // Obsługa zdarzeń...
                // Ten kod będzie rozszerzony w pełnej implementacji
            }
        });
    </script>
    <?php endif; ?>
    
    <!-- Inicjalizacja tooltipów i popoverów -->
    <script>
        $(document).ready(function() {
            // Inicjalizacja tooltipów
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Inicjalizacja popoverów
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Automatyczne ukrywanie alertów po 5 sekundach
            setTimeout(function() {
                $('.alert:not(.alert-important)').fadeOut(500);
            }, 5000);
        });
    </script>
</body>
</html>
