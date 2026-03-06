<!-- <?php
echo "admin123: " . password_hash("admin123", PASSWORD_BCRYPT) . "<br>";
echo "mentor123: " . password_hash("mentor123", PASSWORD_BCRYPT) . "<br>";
echo "mentee123: " . password_hash("mentee123", PASSWORD_BCRYPT) . "<br>";
?> -->
<?php
echo password_hash("mentor123", PASSWORD_DEFAULT);
?>
