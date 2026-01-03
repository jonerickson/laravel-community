<script>
    window.addEventListener('copy-to-clipboard', (event) => {
        navigator.clipboard
            .writeText(event.detail.text)
            .then(() => {
                new FilamentNotification().success().title('The item has been successfully copied.').send();
            })
            .catch((err) => console.error('Clipboard error:', err));
    });
</script>
