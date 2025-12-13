<?php
require 'config.php';

if ($mysqli->ping()) {
    echo "✅ Database connection is working!";
} else {
    echo "❌ Failed to connect to database: " . $mysqli->error;
}
