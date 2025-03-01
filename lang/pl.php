<?php
/**
 * lang/pl.php
 * Polskie tłumaczenia dla aplikacji Q-ZTWS
 */

$translations = [
    // Ogólne
    'app_name' => 'Q-ZTWS',
    'dashboard' => 'Panel główny',
    'login' => 'Logowanie',
    'logout' => 'Wyloguj',
    'register' => 'Rejestracja',
    'profile' => 'Profil',
    'settings' => 'Ustawienia',
    'admin' => 'Administrator',
    'save' => 'Zapisz',
    'save_changes' => 'Zapisz zmiany',
    'cancel' => 'Anuluj',
    'delete' => 'Usuń',
    'edit' => 'Edytuj',
    'create' => 'Utwórz',
    'search' => 'Szukaj',
    'back' => 'Powrót',
    'actions' => 'Akcje',
    'yes' => 'Tak',
    'no' => 'Nie',
    'submit' => 'Wyślij',
    'confirm' => 'Potwierdź',
    'success' => 'Sukces',
    'error' => 'Błąd',
    'warning' => 'Ostrzeżenie',
    'info' => 'Informacja',
    'status' => 'Status',
    'active' => 'Aktywny',
    'inactive' => 'Nieaktywny',
    'pending' => 'Oczekujący',
    'completed' => 'Zakończony',
    'archived' => 'Zarchiwizowany',
    'draft' => 'Szkic',
    'date' => 'Data',
    'time' => 'Czas',
    'seconds' => 'sekund',
    'required' => 'Wymagane',
    'optional' => 'Opcjonalne',
    'language' => 'Język',
    'privacy_policy' => 'Polityka prywatności',
    'terms_of_service' => 'Warunki korzystania z usługi',
    
    // Autoryzacja
    'email' => 'E-mail',
    'password' => 'Hasło',
    'confirm_password' => 'Potwierdź hasło',
    'remember_me' => 'Zapamiętaj mnie',
    'forgot_password' => 'Zapomniałeś hasła?',
    'login_to_account' => 'Zaloguj się do konta',
    'dont_have_account' => 'Nie masz konta?',
    'already_have_account' => 'Masz już konto?',
    'create_account' => 'Utwórz konto',
    'reset_password' => 'Resetuj hasło',
    'change_password' => 'Zmień hasło',
    'current_password' => 'Aktualne hasło',
    'new_password' => 'Nowe hasło',
    'first_name' => 'Imię',
    'last_name' => 'Nazwisko',
    'nickname' => 'Pseudonim',
    'register_success' => 'Rejestracja zakończona sukcesem. Sprawdź swoją skrzynkę e-mail, aby aktywować konto.',
    'login_invalid' => 'Nieprawidłowy adres e-mail lub hasło.',
    'login_account_not_activated' => 'Konto nie zostało jeszcze aktywowane. Sprawdź swoją skrzynkę e-mail.',
    'login_account_inactive' => 'Konto jest nieaktywne. Skontaktuj się z administratorem.',
    'login_empty_fields' => 'Wprowadź adres e-mail i hasło.',
    'login_too_many_attempts' => 'Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za %s minut.',
    'activation_success' => 'Konto zostało aktywowane. Możesz się teraz zalogować.',
    'activation_invalid' => 'Nieprawidłowy kod aktywacyjny.',
    'activation_error' => 'Wystąpił błąd podczas aktywacji konta.',
    'forgot_password_email_sent' => 'Instrukcje dotyczące resetowania hasła zostały wysłane na podany adres e-mail.',
    'forgot_password_email_invalid' => 'Podaj prawidłowy adres e-mail.',
    'forgot_password_account_not_activated' => 'Konto nie zostało jeszcze aktywowane.',
    'forgot_password_account_inactive' => 'Konto jest nieaktywne.',
    'reset_password_token_invalid' => 'Nieprawidłowy lub wygasły token resetowania hasła.',
    'reset_password_password_required' => 'Wprowadź nowe hasło.',
    'reset_password_password_too_short' => 'Hasło musi zawierać co najmniej 8 znaków.',
    'reset_password_passwords_not_match' => 'Hasła nie są identyczne.',
    'reset_password_success' => 'Hasło zostało zmienione. Możesz się teraz zalogować.',
    'reset_password_error' => 'Wystąpił błąd podczas resetowania hasła.',
    'csrf_error' => 'Weryfikacja formularza nie powiodła się. Spróbuj ponownie.',
    
    // Dashboard
    'my_quizzes' => 'Moje quizy',
    'create_quiz' => 'Utwórz quiz',
    'create_first_quiz' => 'Utwórz pierwszy quiz',
    'no_quizzes_yet' => 'Nie masz jeszcze żadnych quizów.',
    'title' => 'Tytuł',
    'questions' => 'Pytania',
    'created_at' => 'Data utworzenia',
    'start_quiz' => 'Rozpocznij quiz',
    'view_all_quizzes' => 'Zobacz wszystkie quizy',
    'recent_sessions' => 'Ostatnie sesje',
    'no_sessions_yet' => 'Nie masz jeszcze żadnych sesji.',
    'participants' => 'Uczestnicy',
    'view_results' => 'Zobacz wyniki',
    'host_session' => 'Prowadź sesję',
    'quick_start' => 'Szybki start',
    'dashboard_welcome_message' => 'Witaj w aplikacji Q-ZTWS! Utwórz swój pierwszy quiz lub dołącz do istniejącego.',
    'join_quiz' => 'Dołącz do quizu',
    'help_tips' => 'Wskazówki',
    'how_to_create_quiz' => 'Jak utworzyć quiz?',
    'how_to_create_quiz_text' => 'Kliknij przycisk "Utwórz quiz", podaj tytuł i opis, a następnie dodaj pytania i odpowiedzi.',
    'how_to_start_quiz' => 'Jak rozpocząć quiz?',
    'how_to_start_quiz_text' => 'Przejdź do edycji quizu, a następnie kliknij przycisk "Rozpocznij quiz". Uczestnicy mogą dołączyć za pomocą kodu dostępu.',
    'how_to_share_quiz' => 'Jak udostępnić quiz?',
    'how_to_share_quiz_text' => 'Udostępnij uczestnikom kod dostępu lub kod QR. Mogą oni dołączyć do quizu za pomocą strony "Dołącz do quizu".',
    
    // Quizy
    'quiz_details' => 'Szczegóły quizu',
    'edit_quiz' => 'Edytuj quiz',
    'back_to_quizzes' => 'Powrót do quizów',
    'quiz_title_required' => 'Tytuł quizu jest wymagany.',
    'quiz_created' => 'Quiz został utworzony.',
    'quiz_updated' => 'Quiz został zaktualizowany.',
    'quiz_deleted' => 'Quiz został usunięty.',
    'quiz_create_error' => 'Wystąpił błąd podczas tworzenia quizu.',
    'quiz_update_error' => 'Wystąpił błąd podczas aktualizacji quizu.',
    'quiz_delete_error' => 'Wystąpił błąd podczas usuwania quizu.',
    'delete_quiz' => 'Usuń quiz',
    'delete_quiz_confirm' => 'Czy na pewno chcesz usunąć ten quiz?',
    'delete_quiz_warning' => 'Ta operacja jest nieodwracalna. Wszystkie pytania, odpowiedzi i wyniki zostaną usunięte.',
    'quiz_not_found' => 'Quiz nie został znaleziony.',
    'quiz_not_owned' => 'Nie jesteś właścicielem tego quizu.',
    'description' => 'Opis',
    
    // Pytania
    'question_text' => 'Treść pytania',
    'question_type' => 'Typ pytania',
    'single_choice' => 'Jednokrotny wybór',
    'multiple_choice' => 'Wielokrotny wybór',
    'true_false' => 'Prawda/Fałsz',
    'time_limit' => 'Limit czasu',
    'points' => 'Punkty',
    'answers' => 'Odpowiedzi',
    'answer_text' => 'Treść odpowiedzi',
    'answer_yes' => 'Tak',
    'answer_no' => 'Nie',
    'add_question' => 'Dodaj pytanie',
    'edit_question' => 'Edytuj pytanie',
    'delete_question' => 'Usuń pytanie',
    'delete_question_confirm' => 'Czy na pewno chcesz usunąć to pytanie?',
    'no_questions_yet' => 'Ten quiz nie ma jeszcze żadnych pytań.',
    'add_first_question' => 'Dodaj pierwsze pytanie',
    'question_added' => 'Pytanie zostało dodane.',
    'question_updated' => 'Pytanie zostało zaktualizowane.',
    'question_deleted' => 'Pytanie zostało usunięte.',
    'question_add_error' => 'Wystąpił błąd podczas dodawania pytania.',
    'question_update_error' => 'Wystąpił błąd podczas aktualizacji pytania.',
    'question_delete_error' => 'Wystąpił błąd podczas usuwania pytania.',
    'question_text_required' => 'Treść pytania jest wymagana.',
    'question_type_invalid' => 'Nieprawidłowy typ pytania.',
    'question_time_limit_invalid' => 'Limit czasu musi być między 5 a 300 sekund.',
    'question_points_invalid' => 'Liczba punktów musi być między 1 a 1000.',
    'question_answers_min' => 'Pytanie musi mieć co najmniej 2 odpowiedzi.',
    'question_correct_answer_required' => 'Wybierz co najmniej jedną poprawną odpowiedź.',
    'question_single_multiple_correct' => 'Pytanie jednokrotnego wyboru może mieć tylko jedną poprawną odpowiedź.',
    'question_not_found' => 'Pytanie nie zostało znalezione.',
    'question_not_in_quiz' => 'Pytanie nie należy do tego quizu.',
    'question_reordered' => 'Kolejność pytań została zmieniona.',
    'question_reorder_error' => 'Wystąpił błąd podczas zmiany kolejności pytań.',
    
    // Udostępnianie
    'share_quiz' => 'Udostępnij quiz',
    'access_code' => 'Kod dostępu',
    'regenerate_code' => 'Wygeneruj nowy kod',
    'qr_code' => 'Kod QR',
    'download_qr' => 'Pobierz kod QR',
    'share_code_instructions' => 'Podaj ten kod uczestnikom, aby mogli dołączyć do quizu.',
    'share_qr_instructions' => 'Uczestnicy mogą zeskanować ten kod QR, aby dołączyć do quizu.',
    'quiz_code_regenerated' => 'Kod dostępu został wygenerowany ponownie.',
    'quiz_code_regenerate_error' => 'Wystąpił błąd podczas generowania nowego kodu dostępu.',
    'qr_code_not_generated' => 'Kod QR nie został jeszcze wygenerowany. Zapisz zmiany, aby wygenerować kod QR.',
    'start_quiz_session' => 'Rozpocznij sesję quizu',
    'start_quiz_instructions' => 'Kliknij przycisk poniżej, aby rozpocząć nową sesję quizu. Uczestnicy będą mogli dołączyć za pomocą kodu dostępu.',
    'add_questions_to_start' => 'Dodaj pytania, aby móc rozpocząć quiz.',
    'quiz_no_questions' => 'Quiz nie ma żadnych pytań. Dodaj pytania przed rozpoczęciem sesji.',
    
    // Eksport
    'export_results' => 'Eksportuj wyniki',
    'export_results_instructions' => 'Wyeksportuj wyniki wszystkich sesji tego quizu do pliku PDF lub Excel.',
    'export_pdf' => 'Eksportuj do PDF',
    'export_xlsx' => 'Eksportuj do Excel',
    'quiz_no_sessions' => 'Ten quiz nie ma żadnych sesji.',
    'quiz_results' => 'Wyniki quizu',
    'quiz_questions' => 'Pytania quizu',
    'quiz_summary' => 'Podsumowanie quizu',
    'total_points' => 'Suma punktów',
    
    // Sesje
    'session' => 'Sesja',
    'sessions' => 'Sesje',
    'session_started' => 'Sesja quizu została rozpoczęta.',
    'session_start_error' => 'Wystąpił błąd podczas rozpoczynania sesji quizu.',
    'session_ended' => 'Sesja quizu została zakończona.',
    'session_end_error' => 'Wystąpił błąd podczas kończenia sesji quizu.',
    'session_not_found' => 'Sesja nie została znaleziona.',
    'session_not_active' => 'Ta sesja nie jest aktywna.',
    'no_current_question' => 'Brak aktualnego pytania.',
    'question_changed' => 'Pytanie zostało zmienione.',
    'question_change_error' => 'Wystąpił błąd podczas zmiany pytania.',
    'answers_shown' => 'Odpowiedzi zostały pokazane.',
    'no_participants' => 'Brak uczestników.',
    'participant_not_found' => 'Uczestnik nie został znaleziony.',
    'score' => 'Wynik',
    'average_time' => 'Średni czas',
    'nickname_taken' => 'Ten pseudonim jest już zajęty w tej sesji.',
    
    // Dołączanie
    'join' => 'Dołącz',
    'game_code' => 'Kod gry',
    'enter_game_code' => 'Wprowadź kod gry',
    'enter_nickname' => 'Wprowadź pseudonim',
    'code_format_info' => 'Kod składa się z 6 znaków (litery i cyfry)',
    'join_quiz_instructions' => 'Wprowadź kod gry i pseudonim, aby dołączyć do quizu.',
    'join_access_code_required' => 'Kod dostępu jest wymagany.',
    'join_nickname_required' => 'Pseudonim jest wymagany.',
    'join_nickname_length' => 'Pseudonim musi mieć od 3 do 20 znaków.',
    'join_invalid_code' => 'Nieprawidłowy kod dostępu.',
    'join_no_active_session' => 'Nie ma aktywnej sesji dla tego quizu.',
    'join_nickname_taken' => 'Ten pseudonim jest już zajęty w tej sesji.',
    'join_error' => 'Wystąpił błąd podczas dołączania do quizu.',
    'how_to_join' => 'Jak dołączyć',
    'join_step_1' => 'Poproś prowadzącego o kod gry lub zeskanuj kod QR.',
    'join_step_2' => 'Wprowadź kod gry w polu powyżej.',
    'join_step_3' => 'Wpisz swój pseudonim.',
    'join_step_4' => 'Kliknij przycisk "Dołącz" i czekaj na rozpoczęcie gry.',
    'scan_qr_code' => 'Skanuj kod QR',
    'qr_code_instructions' => 'Możesz też zeskanować kod QR, aby dołączyć do quizu.',
    'are_you_a_teacher' => 'Jesteś nauczycielem? Zaloguj się, aby tworzyć własne quizy.',
    
    // Odpowiedzi
    'answer_required' => 'Wybierz odpowiedź.',
    'single_choice_only' => 'Możesz wybrać tylko jedną odpowiedź.',
    'already_answered' => 'Już odpowiedziałeś na to pytanie.',
    'answer_recorded' => 'Twoja odpowiedź została zapisana.',
    'answer_error' => 'Wystąpił błąd podczas zapisywania odpowiedzi.',
    
    // Administracja
    'admin_dashboard' => 'Panel administratora',
    'user_management' => 'Zarządzanie użytkownikami',
    'email_templates' => 'Szablony e-mail',
    'users' => 'Użytkownicy',
    'add_user' => 'Dodaj użytkownika',
    'role' => 'Rola',
    'quota' => 'Limit',
    'user_created' => 'Użytkownik został utworzony.',
    'user_updated' => 'Użytkownik został zaktualizowany.',
    'user_deleted' => 'Użytkownik został usunięty.',
    'user_create_error' => 'Wystąpił błąd podczas tworzenia użytkownika.',
    'user_update_error' => 'Wystąpił błąd podczas aktualizacji użytkownika.',
    'user_delete_error' => 'Wystąpił błąd podczas usuwania użytkownika.',
    'user_not_found' => 'Użytkownik nie został znaleziony.',
    'user_delete_self' => 'Nie możesz usunąć własnego konta.',
    'user_email_exists' => 'Użytkownik o podanym adresie e-mail już istnieje.',
    'user_first_name_required' => 'Imię jest wymagane.',
    'user_last_name_required' => 'Nazwisko jest wymagane.',
    'user_email_required' => 'Adres e-mail jest wymagany.',
    'user_email_invalid' => 'Nieprawidłowy adres e-mail.',
    'user_password_required' => 'Hasło jest wymagane.',
    'user_password_too_short' => 'Hasło musi mieć co najmniej 8 znaków.',
    'user_passwords_not_match' => 'Hasła nie są identyczne.',
    'user_role_invalid' => 'Nieprawidłowa rola użytkownika.',
    'user_status_invalid' => 'Nieprawidłowy status użytkownika.',
    'user_language_invalid' => 'Nieprawidłowy język użytkownika.',
    'user_quota_invalid' => 'Nieprawidłowy limit dla użytkownika.',
    'settings_updated' => 'Ustawienia zostały zaktualizowane.',
    'settings_update_error' => 'Wystąpił błąd podczas aktualizacji ustawień.',
    'email_template_not_found' => 'Szablon e-mail nie został znaleziony.',
    'email_template_updated' => 'Szablon e-mail został zaktualizowany.',
    'email_template_update_error' => 'Wystąpił błąd podczas aktualizacji szablonu e-mail.',
    'email_template_subject_pl_required' => 'Temat w języku polskim jest wymagany.',
    'email_template_subject_en_required' => 'Temat w języku angielskim jest wymagany.',
    'email_template_body_pl_required' => 'Treść w języku polskim jest wymagana.',
    'email_template_body_en_required' => 'Treść w języku angielskim jest wymagana.',
    'test_email_sent' => 'Testowy e-mail został wysłany.',
    'test_email_error' => 'Wystąpił błąd podczas wysyłania testowego e-maila.',
    'access_denied' => 'Brak dostępu.',
];

return $translations;
