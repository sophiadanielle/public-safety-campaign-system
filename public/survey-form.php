<?php
/**
 * Public Survey Form Page
 * Accessed via generated QR code/link
 * Users fill out their details and answer survey questions
 */

$pageTitle = 'Survey Form';
require_once __DIR__ . '/../header/includes/path_helper.php';

// Get survey ID from URL
$surveyId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$formToken = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #1e293b;
        }
        
        .header {
            background: linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);
            padding: 20px;
            text-align: center;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 4px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .card-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .form-field {
            margin-bottom: 20px;
        }
        
        .form-field label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-field label .required {
            color: #dc2626;
        }
        
        .form-field input,
        .form-field select,
        .form-field textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            outline: none;
            border-color: #4c8a89;
            box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .checkbox-field {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 20px 0;
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }
        
        .checkbox-field input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #4c8a89;
        }
        
        .checkbox-field label {
            font-size: 14px;
            color: #475569;
            line-height: 1.5;
            cursor: pointer;
        }
        
        .checkbox-field label a {
            color: #4c8a89;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 138, 137, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .question-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid #4c8a89;
        }
        
        .question-text {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .rating-container {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .rating-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }
        
        .rating-option input {
            width: 24px;
            height: 24px;
            accent-color: #4c8a89;
        }
        
        .rating-option span {
            margin-top: 4px;
            font-size: 14px;
            color: #64748b;
        }
        
        .radio-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .radio-option input {
            width: 18px;
            height: 18px;
            accent-color: #4c8a89;
        }
        
        .status-message {
            padding: 16px;
            border-radius: 10px;
            margin-top: 16px;
            font-size: 14px;
            display: none;
        }
        
        .status-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .hidden {
            display: none !important;
        }
        
        /* Terms Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            display: none;
            overflow-y: auto;
        }
        
        .modal-container {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            margin: 40px auto;
            padding: 0;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);
            border-radius: 16px 16px 0 0;
            position: sticky;
            top: 0;
        }
        
        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-body h3 {
            color: #1e293b;
            font-size: 16px;
            margin: 16px 0 8px 0;
        }
        
        .modal-body p, .modal-body li {
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 8px;
        }
        
        .modal-body ul {
            padding-left: 20px;
        }
        
        .success-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .success-icon i {
            font-size: 40px;
            color: white;
        }
        
        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 12px;
        }
        
        .success-message {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .error-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .error-icon i {
            font-size: 40px;
            color: white;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-clipboard-list"></i> Public Safety Survey</h1>
        <p>Barangay Alertaraqc - Community Feedback</p>
    </div>
    
    <div class="container">
        <!-- Error State -->
        <div id="errorState" class="card hidden">
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="card-title" style="color:#dc2626;">Survey Not Available</h2>
                <p id="errorMessage" class="card-description">This survey is not available or has been closed.</p>
            </div>
        </div>
        
        <!-- Loading State -->
        <div id="loadingState" class="card">
            <div style="text-align:center; padding:40px;">
                <i class="fas fa-spinner fa-spin" style="font-size:40px; color:#4c8a89; margin-bottom:16px;"></i>
                <p style="color:#64748b;">Loading survey...</p>
            </div>
        </div>
        
        <!-- Respondent Details Form -->
        <div id="respondentForm" class="card hidden">
            <h2 class="card-title" id="surveyTitle">Survey</h2>
            <p class="card-description" id="surveyDescription">Please fill out your details below before answering the survey questions.</p>
            
            <form id="detailsForm">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" id="respondent_name" required placeholder="Juan Dela Cruz">
                    </div>
                    <div class="form-field">
                        <label>Gender <span class="required">*</span></label>
                        <select id="respondent_gender" required>
                            <option value="">-- Select Gender --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Age <span class="required">*</span></label>
                        <input type="number" id="respondent_age" required min="1" max="120" placeholder="25">
                    </div>
                    <div class="form-field">
                        <label>Address <span class="required">*</span></label>
                        <input type="text" id="respondent_address" required placeholder="Purok 1, Barangay Alertaraqc">
                    </div>
                </div>
                
                <div class="checkbox-field">
                    <input type="checkbox" id="terms_checkbox" required>
                    <label for="terms_checkbox">
                        I have read and agree to the <a href="javascript:void(0)" onclick="showTermsModal()">Terms & Conditions</a> and consent to the collection and processing of my personal information for the purpose of this survey.
                    </label>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="proceedToSurvey()" id="proceedBtn">
                    <i class="fas fa-arrow-right"></i> Proceed to Survey
                </button>
            </form>
        </div>
        
        <!-- Survey Questions Form -->
        <div id="surveyForm" class="card hidden">
            <h2 class="card-title" id="surveyTitleQuestions">Survey Questions</h2>
            <p class="card-description">Please answer all required questions below.</p>
            
            <div id="questionsContainer"></div>
            
            <button type="button" class="btn btn-primary" onclick="submitSurvey()" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Response
            </button>
            
            <div id="submitStatus" class="status-message"></div>
        </div>
        
        <!-- Success State -->
        <div id="successState" class="card hidden">
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="success-title">Thank You!</h2>
                <p class="success-message">Your response has been submitted successfully. We appreciate your feedback and participation in making our community safer.</p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Barangay Alertaraqc - Public Safety Campaign System</p>
    </div>
    
    <!-- Terms & Conditions Modal -->
    <div id="termsModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i class="fas fa-file-contract"></i> Terms & Conditions</h2>
                <button class="modal-close" onclick="closeTermsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <h3>1. Purpose of Data Collection</h3>
                <p>The Barangay Alertaraqc Public Safety Campaign System collects survey responses to improve public safety initiatives, evaluate campaign effectiveness, and better serve our community.</p>
                
                <h3>2. Information We Collect</h3>
                <p>We collect the following information:</p>
                <ul>
                    <li>Full Name</li>
                    <li>Gender</li>
                    <li>Age</li>
                    <li>Address</li>
                    <li>Survey responses</li>
                </ul>
                
                <h3>3. Use of Information</h3>
                <p>Your information will be used solely for:</p>
                <ul>
                    <li>Analyzing survey results and community feedback</li>
                    <li>Improving public safety programs and campaigns</li>
                    <li>Statistical reporting (anonymized)</li>
                    <li>Contacting you for follow-up if necessary</li>
                </ul>
                
                <h3>4. Data Protection</h3>
                <p>We are committed to protecting your personal information. Your data will be:</p>
                <ul>
                    <li>Stored securely in our database</li>
                    <li>Accessible only to authorized personnel</li>
                    <li>Not shared with third parties without your consent</li>
                    <li>Retained only for as long as necessary</li>
                </ul>
                
                <h3>5. Your Rights</h3>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal data</li>
                    <li>Request correction of inaccurate data</li>
                    <li>Request deletion of your data</li>
                    <li>Withdraw consent at any time</li>
                </ul>
                
                <h3>6. Contact Information</h3>
                <p>For questions or concerns about your data, please contact the Barangay Alertaraqc office.</p>
                
                <div style="margin-top:24px; text-align:center;">
                    <button class="btn btn-primary" onclick="closeTermsModal()" style="width:auto; padding:12px 32px;">
                        I Understand
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const apiBase = '<?php echo $apiPath; ?>';
const surveyId = <?php echo $surveyId; ?>;
let surveyData = null;
let respondentData = {};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (!surveyId) {
        showError('Invalid survey link. Please use a valid survey URL.');
        return;
    }
    loadSurvey();
});

async function loadSurvey() {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId);
        const data = await res.json();
        
        if (!res.ok || !data.data) {
            showError(data.error || 'Survey not found.');
            return;
        }
        
        surveyData = data.data;
        
        // Check if survey is published
        if (surveyData.status !== 'published') {
            showError('This survey is not currently accepting responses.');
            return;
        }
        
        // Check if survey has questions
        if (!surveyData.questions || surveyData.questions.length === 0) {
            showError('This survey has no questions.');
            return;
        }
        
        // Show respondent form
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('respondentForm').classList.remove('hidden');
        document.getElementById('surveyTitle').textContent = surveyData.title || 'Survey';
        if (surveyData.description) {
            document.getElementById('surveyDescription').textContent = surveyData.description;
        }
        
    } catch (err) {
        showError('Failed to load survey: ' + err.message);
    }
}

function showError(message) {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('errorState').classList.remove('hidden');
    document.getElementById('errorMessage').textContent = message;
}

function showTermsModal() {
    document.getElementById('termsModal').style.display = 'block';
}

function closeTermsModal() {
    document.getElementById('termsModal').style.display = 'none';
}

function proceedToSurvey() {
    const name = document.getElementById('respondent_name').value.trim();
    const gender = document.getElementById('respondent_gender').value;
    const age = document.getElementById('respondent_age').value;
    const address = document.getElementById('respondent_address').value.trim();
    const termsChecked = document.getElementById('terms_checkbox').checked;
    
    if (!name || !gender || !age || !address) {
        alert('Please fill out all required fields.');
        return;
    }
    
    if (!termsChecked) {
        alert('Please agree to the Terms & Conditions to proceed.');
        return;
    }
    
    // Store respondent data
    respondentData = {
        full_name: name,
        gender: gender,
        age: parseInt(age),
        address: address
    };
    
    // Hide respondent form, show survey questions
    document.getElementById('respondentForm').classList.add('hidden');
    document.getElementById('surveyForm').classList.remove('hidden');
    document.getElementById('surveyTitleQuestions').textContent = surveyData.title || 'Survey Questions';
    
    // Render questions
    renderQuestions();
}

function renderQuestions() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';
    
    surveyData.questions.forEach((q, index) => {
        const questionDiv = document.createElement('div');
        questionDiv.className = 'question-card';
        
        let inputHtml = '';
        const questionId = q.id;
        const questionType = q.question_type;
        const required = q.required_flag ? '<span class="required">*</span>' : '';
        
        if (questionType === 'rating') {
            inputHtml = '<div class="rating-container">';
            for (let i = 1; i <= 5; i++) {
                inputHtml += `
                    <label class="rating-option">
                        <input type="radio" name="q_${questionId}" value="${i}" data-qid="${questionId}" ${q.required_flag ? 'required' : ''}>
                        <span>${i}</span>
                    </label>
                `;
            }
            inputHtml += '</div>';
        } else if (questionType === 'yes_no') {
            inputHtml = '<div class="radio-container">';
            ['Yes', 'No'].forEach(opt => {
                inputHtml += `
                    <label class="radio-option">
                        <input type="radio" name="q_${questionId}" value="${opt}" data-qid="${questionId}" ${q.required_flag ? 'required' : ''}>
                        <span>${opt}</span>
                    </label>
                `;
            });
            inputHtml += '</div>';
        } else if (questionType === 'single_choice' || questionType === 'multiple_choice') {
            let options = [];
            try {
                if (typeof q.options_json === 'string') {
                    options = JSON.parse(q.options_json || '[]');
                } else if (Array.isArray(q.options_json)) {
                    options = q.options_json;
                }
            } catch (e) {
                options = [];
            }
            
            if (questionType === 'multiple_choice') {
                inputHtml = `<select data-qid="${questionId}" multiple size="${Math.min(5, options.length)}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;" ${q.required_flag ? 'required' : ''}>`;
            } else {
                inputHtml = `<select data-qid="${questionId}" style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px;" ${q.required_flag ? 'required' : ''}><option value="">-- Select --</option>`;
            }
            options.forEach(opt => {
                inputHtml += `<option value="${opt}">${opt}</option>`;
            });
            inputHtml += '</select>';
        } else {
            inputHtml = `<textarea data-qid="${questionId}" rows="3" placeholder="Type your answer here..." style="width:100%; padding:10px; border:2px solid #e2e8f0; border-radius:8px; font-family:inherit;" ${q.required_flag ? 'required' : ''}></textarea>`;
        }
        
        questionDiv.innerHTML = `
            <div class="question-text">Q${index + 1}: ${q.question_text} ${required}</div>
            ${inputHtml}
        `;
        
        container.appendChild(questionDiv);
    });
}

async function submitSurvey() {
    const submitBtn = document.getElementById('submitBtn');
    const statusEl = document.getElementById('submitStatus');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    statusEl.style.display = 'none';
    
    // Build responses object
    const responses = {};
    const inputs = document.querySelectorAll('[data-qid]');
    
    inputs.forEach(input => {
        const questionId = input.dataset.qid;
        const question = surveyData.questions.find(q => q.id == questionId);
        
        if (!question) return;
        
        if (input.type === 'radio') {
            if (input.checked) {
                if (question.question_type === 'rating') {
                    responses[questionId] = parseInt(input.value, 10);
                } else {
                    responses[questionId] = input.value;
                }
            }
        } else if (input.tagName === 'SELECT' && input.multiple) {
            const selected = Array.from(input.selectedOptions).map(opt => opt.value);
            if (selected.length > 0) {
                responses[questionId] = selected;
            }
        } else {
            const value = input.value.trim();
            if (value) {
                responses[questionId] = value;
            }
        }
    });
    
    // Validate required questions
    const missingRequired = surveyData.questions.filter(q => {
        if (!q.required_flag) return false;
        return !responses.hasOwnProperty(q.id) || 
               (Array.isArray(responses[q.id]) && responses[q.id].length === 0) ||
               (typeof responses[q.id] === 'string' && responses[q.id].trim() === '');
    });
    
    if (missingRequired.length > 0) {
        statusEl.className = 'status-message status-error';
        statusEl.textContent = 'Please answer all required questions.';
        statusEl.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Response';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId + '/responses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                responses: responses,
                respondent: respondentData
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            // Show success state
            document.getElementById('surveyForm').classList.add('hidden');
            document.getElementById('successState').classList.remove('hidden');
        } else {
            statusEl.className = 'status-message status-error';
            statusEl.textContent = 'Error: ' + (data.error || 'Failed to submit response');
            statusEl.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Response';
        }
    } catch (err) {
        statusEl.className = 'status-message status-error';
        statusEl.textContent = 'Network error: ' + err.message;
        statusEl.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Response';
    }
}

// Close modal on outside click
document.getElementById('termsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTermsModal();
    }
});
</script>
</body>
</html>
