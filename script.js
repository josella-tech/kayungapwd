<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Script</title>
</head>
<body>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

document.addEventListener('click', function(e) {
    if (e.target.closest('.menu-toggle')) {
        toggleSidebar();
    }
});
</script>
</body>
</html>