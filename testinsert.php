<?php

include('db.php');

$hashed = password_hash("123456", PASSWORD_BCRYPT);

$sql = "INSERT INTO users(username,email,password)
VALUES('testuser','test@test.com','$hashed')";

if(mysqli_query($conn,$sql)){
    echo "Inserted successfully";
} else {
    echo mysqli_error($conn);
}