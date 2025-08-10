document.addEventListener('DOMContentLoaded', function () {
    const datePicker = document.querySelector('input[name="date_received"]');
    if (datePicker) {
        // Set the max attribute to today's date
        datePicker.setAttribute('max', new Date().toISOString().split("T")[0]);

        // Add an event listener to check for future dates
        datePicker.addEventListener('input', function () {
            if (this.value > new Date().toISOString().split("T")[0]) {
                this.value = ''; // Clear the input if a future date is selected
                alert('Future dates are not allowed.');
            }
        });
    }
});

