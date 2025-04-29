/*!
 * UFP Component
 * Dual Licensed under GPLv3 and Commercial
 * Author: DgtalFairness | https://universalfairnessprotocol.com
 */

document.addEventListener("DOMContentLoaded", () => {
  // Handle submission for all tasks
  document.querySelectorAll(".task-submit-form").forEach(form => {
    form.addEventListener("submit", async e => {
      e.preventDefault();

      const taskIdInput = form.querySelector("input[name='task_id']");
      const inputField = form.querySelector("input[name='submission_text']");
      const messageBox = form.querySelector(".task-submit-message");

      if (!taskIdInput || !inputField) {
        
        return;
      }

      const taskId = taskIdInput.value.trim();
      const submissionText = inputField.value.trim();

      if (!submissionText) {
        if (messageBox) {
          messageBox.innerHTML = `<div class="alert alert-danger">⛔ Please enter your submission.</div>`;
        }
        return;
      }

      if (messageBox) {
        messageBox.innerHTML = `<div class="alert alert-info">⏳ Submitting your response...</div>`;
      }

      try {
        const response = await fetch("/system/ajax.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "submit_task_entry",
            task_id: taskId,
            submission_text: submissionText
          })
        });

        const result = await response.json();

        if (result.success) {
          if (messageBox) {
            messageBox.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
          }
          inputField.disabled = true;
          form.querySelector("button[type='submit']").disabled = true;
        } else {
          if (messageBox) {
            messageBox.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
          }
        }
      } catch (err) {
        
        if (messageBox) {
          messageBox.innerHTML = `<div class="alert alert-danger">⚠️ Something went wrong. Please try again later.</div>`;
        }
      }
    });
  });
});