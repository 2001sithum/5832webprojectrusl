document.getElementById('contactForm').addEventListener('submit', async (event) => {
    event.preventDefault();

    // Get form values
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const message = document.getElementById('message').value.trim();

    // Validate inputs
    if (!name || !email || !message) {
        alert('All fields are required!');
        return;
    }

    // Create a payload to send to the server
    const formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('message', message);

    try {
        // Send the form data to the server
        const response = await fetch('submit.py', {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();

        if (result.success) {
            alert('Your message has been submitted successfully!');
            document.getElementById('contactForm').reset();
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        console.error('Error submitting the form:', error);
        alert('An error occurred. Please try again.');





    }
});