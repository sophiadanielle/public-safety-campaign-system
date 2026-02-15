// JavaScript functions for campaigns.php - Budget toggle and datetime sync

// Toggle Budget Visibility
function toggleBudgetVisibility() {
    const budgetInput = document.getElementById('budget');
    const budgetPlaceholder = document.getElementById('budgetHiddenPlaceholder');
    const eyeIcon = document.getElementById('budgetEyeIcon');
    
    if (budgetInput && budgetPlaceholder && eyeIcon) {
        if (budgetInput.style.display === 'none') {
            // Show budget
            budgetInput.style.display = 'block';
            budgetPlaceholder.style.display = 'none';
            eyeIcon.className = 'fas fa-eye';
        } else {
            // Hide budget
            budgetInput.style.display = 'none';
            budgetPlaceholder.style.display = 'block';
            eyeIcon.className = 'fas fa-eye-slash';
        }
    }
}

// Toggle Total Budget Visibility in Resource Allocation
function toggleTotalBudgetVisibility() {
    const totalBudget = document.getElementById('totalBudget');
    const totalBudgetHidden = document.getElementById('totalBudgetHidden');
    const eyeIcon = document.getElementById('totalBudgetEyeIcon');
    
    if (totalBudget && totalBudgetHidden && eyeIcon) {
        if (totalBudget.style.display === 'none') {
            // Show total budget
            totalBudget.style.display = 'block';
            totalBudgetHidden.style.display = 'none';
            eyeIcon.className = 'fas fa-eye';
        } else {
            // Hide total budget
            totalBudget.style.display = 'none';
            totalBudgetHidden.style.display = 'block';
            eyeIcon.className = 'fas fa-eye-slash';
        }
    }
}

// Sync datetime-local inputs with hidden date and time fields
function syncDateTimeFields() {
    // Sync start datetime
    const startDatetime = document.getElementById('start_datetime');
    const startDate = document.getElementById('start_date');
    const startTime = document.getElementById('start_time');
    
    if (startDatetime && startDate && startTime) {
        startDatetime.addEventListener('change', function() {
            if (this.value) {
                const dt = new Date(this.value);
                const year = dt.getFullYear();
                const month = String(dt.getMonth() + 1).padStart(2, '0');
                const day = String(dt.getDate()).padStart(2, '0');
                const hours = String(dt.getHours()).padStart(2, '0');
                const minutes = String(dt.getMinutes()).padStart(2, '0');
                
                startDate.value = `${year}-${month}-${day}`;
                startTime.value = `${hours}:${minutes}`;
            } else {
                startDate.value = '';
                startTime.value = '';
            }
        });
    }
    
    // Sync end datetime
    const endDatetime = document.getElementById('end_datetime');
    const endDate = document.getElementById('end_date');
    const endTime = document.getElementById('end_time');
    
    if (endDatetime && endDate && endTime) {
        endDatetime.addEventListener('change', function() {
            if (this.value) {
                const dt = new Date(this.value);
                const year = dt.getFullYear();
                const month = String(dt.getMonth() + 1).padStart(2, '0');
                const day = String(dt.getDate()).padStart(2, '0');
                const hours = String(dt.getHours()).padStart(2, '0');
                const minutes = String(dt.getMinutes()).padStart(2, '0');
                
                endDate.value = `${year}-${month}-${day}`;
                endTime.value = `${hours}:${minutes}`;
            } else {
                endDate.value = '';
                endTime.value = '';
            }
        });
    }
}

// Show Archived Campaigns Modal
async function showArchivedCampaigns() {
    try {
        const token = localStorage.getItem('jwtToken');
        const apiBase = window.apiBase || '';
        
        const res = await fetch(apiBase + '/api/v1/campaigns?status=archived', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        const data = await res.json();
        if (data.error) {
            alert('Error loading archived campaigns: ' + data.error);
            return;
        }
        
        const archivedCampaigns = data.data || [];
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'archivedCampaignsModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
        
        let tableRows = '';
        if (archivedCampaigns.length === 0) {
            tableRows = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">No archived campaigns found.</td></tr>';
        } else {
            archivedCampaigns.forEach(c => {
                tableRows += `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.title || 'Untitled'}</td>
                        <td>${c.category || '-'}</td>
                        <td>${c.start_date || '-'}</td>
                        <td>${c.end_date || '-'}</td>
                        <td>
                            <button onclick="restoreCampaign(${c.id})" style="padding: 6px 12px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 4px; font-size: 12px;">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button onclick="deleteCampaignPermanent(${c.id})" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; max-width: 1200px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 20px; font-weight: 700;">
                        <i class="fas fa-archive"></i> Archived Campaigns
                    </h2>
                    <button onclick="document.getElementById('archivedCampaignsModal').remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px;">
                        &times;
                    </button>
                </div>
                <div style="padding: 20px; overflow-y: auto; flex: 1;">
                    <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">
                        Archived campaigns are hidden from the main list. You can restore them or delete them permanently.
                    </p>
                    <div style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">ID</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Title</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Category</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Start Date</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">End Date</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tableRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    } catch (err) {
        alert('Failed to load archived campaigns: ' + err.message);
    }
}

// Restore Campaign from Archive
async function restoreCampaign(campaignId) {
    if (!confirm('Restore this campaign from archive?')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('jwtToken');
        const apiBase = window.apiBase || '';
        
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ status: 'draft' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to restore campaign'));
            return;
        }
        
        alert('Campaign restored successfully!');
        document.getElementById('archivedCampaignsModal').remove();
        if (typeof loadCampaigns === 'function') {
            loadCampaigns();
        }
    } catch (err) {
        alert('Failed to restore campaign: ' + err.message);
    }
}

// Delete Campaign Permanently
async function deleteCampaignPermanent(campaignId) {
    if (!confirm('Permanently delete this campaign? This action cannot be undone.')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('jwtToken');
        const apiBase = window.apiBase || '';
        
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to delete campaign'));
            return;
        }
        
        alert('Campaign deleted permanently!');
        showArchivedCampaigns(); // Refresh the modal
    } catch (err) {
        alert('Failed to delete campaign: ' + err.message);
    }
}

// Archive Campaign
async function archiveCampaign(campaignId) {
    if (!confirm('Archive this campaign? It will be hidden from the main list.')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('jwtToken');
        const apiBase = window.apiBase || '';
        
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ status: 'archived' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to archive campaign'));
            return;
        }
        
        alert('Campaign archived successfully!');
        if (typeof loadCampaigns === 'function') {
            loadCampaigns();
        }
    } catch (err) {
        alert('Failed to archive campaign: ' + err.message);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    syncDateTimeFields();
});
