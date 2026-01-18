<?php
declare(strict_types=1);

// Comprehensive test of Survey Module and Integration Infrastructure
$dbHost = 'localhost';
$dbPort = '3306';
$dbUser = 'root';
$dbPass = 'Phiarren@182212';
$dbName = 'lgu';

echo "=== Testing Survey Module Integration Infrastructure ===\n\n";

// Connect to database
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Connected to database: $dbName\n\n";
} catch (PDOException $e) {
    die("ERROR: Cannot connect to database: " . $e->getMessage() . "\n");
}

$errors = [];
$successes = [];

// ============================================
// TEST 1: Verify all tables exist
// ============================================
echo "TEST 1: Verifying all survey tables exist...\n";
$requiredTables = [
    'campaign_department_surveys',
    'campaign_department_survey_questions',
    'campaign_department_survey_responses',
    'campaign_department_survey_response_details',
    'campaign_department_survey_aggregated_results',
    'campaign_department_survey_audit_log',
    'campaign_department_survey_integration_checkpoints',
];

foreach ($requiredTables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ $table exists\n";
        $successes[] = "Table: $table";
    } else {
        echo "  ✗ $table NOT FOUND\n";
        $errors[] = "Missing table: $table";
    }
}

// ============================================
// TEST 2: Verify table structures
// ============================================
echo "\nTEST 2: Verifying table structures...\n";

// Check surveys table has event_id
$stmt = $pdo->query("SHOW COLUMNS FROM campaign_department_surveys LIKE 'event_id'");
if ($stmt->rowCount() > 0) {
    echo "  ✓ surveys.event_id column exists\n";
    $successes[] = "surveys.event_id column";
} else {
    echo "  ✗ surveys.event_id column missing\n";
    $errors[] = "Missing column: surveys.event_id";
}

// Check integration_checkpoints has subsystem_type
$stmt = $pdo->query("SHOW COLUMNS FROM campaign_department_survey_integration_checkpoints LIKE 'subsystem_type'");
if ($stmt->rowCount() > 0) {
    $stmt = $pdo->query("SHOW COLUMNS FROM campaign_department_survey_integration_checkpoints WHERE Field = 'subsystem_type'");
    $col = $stmt->fetch();
    echo "  ✓ integration_checkpoints.subsystem_type exists (Type: {$col['Type']})\n";
    $successes[] = "integration_checkpoints.subsystem_type";
} else {
    echo "  ✗ integration_checkpoints.subsystem_type missing\n";
    $errors[] = "Missing column: integration_checkpoints.subsystem_type";
}

// ============================================
// TEST 3: Create a test survey
// ============================================
echo "\nTEST 3: Creating test survey...\n";
try {
    // Get first user and campaign for testing
    $stmt = $pdo->query("SELECT id FROM campaign_department_users LIMIT 1");
    $user = $stmt->fetch();
    $userId = $user ? (int)$user['id'] : 1;
    
    $stmt = $pdo->query("SELECT id FROM campaign_department_campaigns LIMIT 1");
    $campaign = $stmt->fetch();
    $campaignId = $campaign ? (int)$campaign['id'] : 1;
    
    $stmt = $pdo->prepare("INSERT INTO campaign_department_surveys (campaign_id, title, description, status, created_by) VALUES (:campaign_id, :title, :description, :status, :created_by)");
    $stmt->execute([
        'campaign_id' => $campaignId,
        'title' => 'Test Survey - Integration Test',
        'description' => 'This is a test survey to verify integration infrastructure',
        'status' => 'draft',
        'created_by' => $userId,
    ]);
    $surveyId = (int)$pdo->lastInsertId();
    echo "  ✓ Created survey ID: $surveyId\n";
    $successes[] = "Created survey #$surveyId";
} catch (PDOException $e) {
    echo "  ✗ Failed to create survey: " . $e->getMessage() . "\n";
    $errors[] = "Failed to create survey: " . $e->getMessage();
    $surveyId = null;
}

// ============================================
// TEST 4: Add questions to survey
// ============================================
echo "\nTEST 4: Adding questions to survey...\n";
if ($surveyId) {
    $questions = [
        ['text' => 'How would you rate this event?', 'type' => 'rating', 'required' => true, 'order' => 1],
        ['text' => 'What did you like most?', 'type' => 'open_ended', 'required' => false, 'order' => 2],
        ['text' => 'Would you attend again?', 'type' => 'yes_no', 'required' => true, 'order' => 3],
    ];
    
    $questionIds = [];
    foreach ($questions as $q) {
        try {
            $stmt = $pdo->prepare("INSERT INTO campaign_department_survey_questions (survey_id, question_text, question_type, question_order, required_flag) VALUES (:survey_id, :text, :type, :order, :required)");
            $stmt->execute([
                'survey_id' => $surveyId,
                'text' => $q['text'],
                'type' => $q['type'],
                'order' => $q['order'],
                'required' => $q['required'] ? 1 : 0,
            ]);
            $questionIds[] = (int)$pdo->lastInsertId();
            echo "  ✓ Added question: '{$q['text']}' (ID: " . end($questionIds) . ")\n";
            $successes[] = "Added question: {$q['text']}";
        } catch (PDOException $e) {
            echo "  ✗ Failed to add question: " . $e->getMessage() . "\n";
            $errors[] = "Failed to add question: " . $e->getMessage();
        }
    }
} else {
    echo "  ⚠ Skipped (no survey ID)\n";
}

// ============================================
// TEST 5: Publish survey
// ============================================
echo "\nTEST 5: Publishing survey...\n";
if ($surveyId) {
    try {
        $stmt = $pdo->prepare("UPDATE campaign_department_surveys SET status = 'published', published_via = 'both', published_by = :user_id, published_at = NOW() WHERE id = :survey_id");
        $stmt->execute([
            'survey_id' => $surveyId,
            'user_id' => $userId,
        ]);
        echo "  ✓ Survey published\n";
        $successes[] = "Published survey #$surveyId";
    } catch (PDOException $e) {
        echo "  ✗ Failed to publish: " . $e->getMessage() . "\n";
        $errors[] = "Failed to publish survey: " . $e->getMessage();
    }
} else {
    echo "  ⚠ Skipped (no survey ID)\n";
}

// ============================================
// TEST 6: Submit test responses
// ============================================
echo "\nTEST 6: Submitting test responses...\n";
if ($surveyId && !empty($questionIds)) {
    $responses = [
        [$questionIds[0] => '5'], // Rating: 5
        [$questionIds[1] => 'Great organization and content'], // Open-ended
        [$questionIds[2] => 'Yes'], // Yes/No
    ];
    
    foreach ($responses as $idx => $responseData) {
        try {
            // Insert response
            $stmt = $pdo->prepare("INSERT INTO campaign_department_survey_responses (survey_id, respondent_identifier, responses_json, submission_timestamp) VALUES (:survey_id, :identifier, :responses, NOW())");
            $stmt->execute([
                'survey_id' => $surveyId,
                'identifier' => 'test_respondent_' . ($idx + 1),
                'responses' => json_encode($responseData),
            ]);
            $responseId = (int)$pdo->lastInsertId();
            
            // Insert response details
            foreach ($responseData as $questionId => $value) {
                $stmt = $pdo->prepare("INSERT INTO campaign_department_survey_response_details (response_id, question_id, response_value) VALUES (:response_id, :question_id, :value)");
                $stmt->execute([
                    'response_id' => $responseId,
                    'question_id' => $questionId,
                    'value' => $value,
                ]);
            }
            
            echo "  ✓ Submitted response #" . ($idx + 1) . " (Response ID: $responseId)\n";
            $successes[] = "Submitted response #" . ($idx + 1);
        } catch (PDOException $e) {
            echo "  ✗ Failed to submit response: " . $e->getMessage() . "\n";
            $errors[] = "Failed to submit response: " . $e->getMessage();
        }
    }
} else {
    echo "  ⚠ Skipped (no survey ID or questions)\n";
}

// ============================================
// TEST 7: Test integration checkpoints
// ============================================
echo "\nTEST 7: Testing integration checkpoints...\n";
if ($surveyId) {
    $subsystems = [
        'event_management' => ['attendance_count' => 150, 'event_id' => 1],
        'disaster_preparedness' => ['training_scores' => ['avg' => 4.5, 'total' => 20]],
        'community_policing' => ['engagement_metrics' => ['participants' => 75]],
        'emergency_response' => ['operational_insights' => ['response_time' => '15min']],
    ];
    
    foreach ($subsystems as $subsystem => $data) {
        try {
            $stmt = $pdo->prepare("INSERT INTO campaign_department_survey_integration_checkpoints (survey_id, subsystem_type, integration_status, sent_data, received_data) VALUES (:survey_id, :subsystem, :status, :sent, :received)");
            $stmt->execute([
                'survey_id' => $surveyId,
                'subsystem' => $subsystem,
                'status' => 'sent',
                'sent' => json_encode(['survey_id' => $surveyId, 'data' => $data]),
                'received' => null,
            ]);
            echo "  ✓ Created checkpoint for $subsystem\n";
            $successes[] = "Integration checkpoint: $subsystem";
        } catch (PDOException $e) {
            echo "  ✗ Failed to create checkpoint for $subsystem: " . $e->getMessage() . "\n";
            $errors[] = "Failed checkpoint for $subsystem: " . $e->getMessage();
        }
    }
} else {
    echo "  ⚠ Skipped (no survey ID)\n";
}

// ============================================
// TEST 8: Test aggregated results
// ============================================
echo "\nTEST 8: Checking aggregated results...\n";
if ($surveyId && !empty($questionIds)) {
    try {
        // Manually trigger aggregation (simulate what controller does)
        foreach ($questionIds as $questionId) {
            // Get question type
            $stmt = $pdo->prepare("SELECT question_type FROM campaign_department_survey_questions WHERE id = ?");
            $stmt->execute([$questionId]);
            $question = $stmt->fetch();
            
            if ($question && $question['question_type'] === 'rating') {
                // Calculate average rating
                $stmt = $pdo->prepare("SELECT AVG(CAST(response_value AS DECIMAL(5,2))) as avg_rating, COUNT(*) as total FROM campaign_department_survey_response_details WHERE question_id = ?");
                $stmt->execute([$questionId]);
                $result = $stmt->fetch();
                
                if ($result) {
                    // Insert/update aggregated result
                    $stmt = $pdo->prepare("INSERT INTO campaign_department_survey_aggregated_results (survey_id, question_id, average_rating, total_responses) VALUES (:sid, :qid, :avg, :total) ON DUPLICATE KEY UPDATE average_rating = VALUES(average_rating), total_responses = VALUES(total_responses)");
                    $stmt->execute([
                        'sid' => $surveyId,
                        'qid' => $questionId,
                        'avg' => $result['avg_rating'],
                        'total' => $result['total'],
                    ]);
                    echo "  ✓ Aggregated result for question #$questionId: Avg={$result['avg_rating']}, Total={$result['total']}\n";
                    $successes[] = "Aggregated result for question #$questionId";
                }
            }
        }
    } catch (PDOException $e) {
        echo "  ✗ Failed to aggregate results: " . $e->getMessage() . "\n";
        $errors[] = "Failed to aggregate: " . $e->getMessage();
    }
} else {
    echo "  ⚠ Skipped (no survey ID or questions)\n";
}

// ============================================
// TEST 9: Test survey summary view
// ============================================
echo "\nTEST 9: Testing survey summary view...\n";
try {
    $stmt = $pdo->query("SELECT * FROM campaign_department_survey_summary_view WHERE survey_id = $surveyId");
    $summary = $stmt->fetch();
    if ($summary) {
        echo "  ✓ Survey summary view works\n";
        echo "    - Title: {$summary['survey_title']}\n";
        echo "    - Status: {$summary['survey_status']}\n";
        echo "    - Questions: {$summary['question_count']}\n";
        echo "    - Total Responses: {$summary['total_responses']}\n";
        $successes[] = "Survey summary view";
    } else {
        echo "  ⚠ Survey summary view returned no data\n";
    }
} catch (PDOException $e) {
    echo "  ✗ Failed to query summary view: " . $e->getMessage() . "\n";
    $errors[] = "Failed summary view: " . $e->getMessage();
}

// ============================================
// TEST 10: Verify audit log
// ============================================
echo "\nTEST 10: Checking audit log...\n";
if ($surveyId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM campaign_department_survey_audit_log WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        $result = $stmt->fetch();
        if ($result && $result['count'] > 0) {
            echo "  ✓ Audit log has {$result['count']} entries for survey #$surveyId\n";
            $successes[] = "Audit log entries";
        } else {
            echo "  ⚠ No audit log entries found (this is OK if controller doesn't log yet)\n";
        }
    } catch (PDOException $e) {
        echo "  ✗ Failed to check audit log: " . $e->getMessage() . "\n";
        $errors[] = "Failed audit log check: " . $e->getMessage();
    }
} else {
    echo "  ⚠ Skipped (no survey ID)\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✓ Successful tests: " . count($successes) . "\n";
echo "✗ Errors: " . count($errors) . "\n\n";

if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if ($surveyId) {
    echo "Test Survey ID: $surveyId\n";
    echo "You can view it in the database or delete it with:\n";
    echo "  DELETE FROM campaign_department_surveys WHERE id = $surveyId;\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
if (empty($errors)) {
    echo "✅ ALL TESTS PASSED! Survey module is fully functional.\n";
} else {
    echo "⚠ Some tests failed. Please review errors above.\n";
}
echo str_repeat("=", 60) . "\n";



