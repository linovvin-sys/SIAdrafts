// escHtml()/spToast() are shared globally via Frontend/Js/Student/shared.js.
document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  document.body.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-start-quiz]');
    if (!btn) return;

    btn.disabled = true;
    const quizId = btn.dataset.startQuiz;
    const body = new FormData();
    body.append('quiz_id', quizId);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Quizzes/start_attempt.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error && !d.attempt) {
          if (window.spToast) window.spToast(d.error, 'mdi:alert-circle-outline');
          btn.disabled = false;
          return;
        }
        window.location.href = '/SIAdrafts/Frontend/View/Student/take_quiz.php?attempt_id=' + d.attempt.attempt_id;
      })
      .catch(() => {
        if (window.spToast) window.spToast('Could not start the quiz. Please try again.', 'mdi:alert-circle-outline');
        btn.disabled = false;
      });
  });
});
