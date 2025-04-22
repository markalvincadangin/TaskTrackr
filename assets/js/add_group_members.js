document.addEventListener('DOMContentLoaded', function () {
    const addMemberBtn = document.getElementById('addMemberBtn');
    const emailFieldsContainer = document.getElementById('emailFields');

    if (addMemberBtn && emailFieldsContainer) {
        addMemberBtn.addEventListener('click', function () {
            // Create a new input element
            const newEmailField = document.createElement('input');
            newEmailField.type = 'email';
            newEmailField.name = 'member_emails[]';
            newEmailField.required = true;

            // Append input and a line break
            emailFieldsContainer.appendChild(newEmailField);
            emailFieldsContainer.appendChild(document.createElement('br'));
        });
    }
});
