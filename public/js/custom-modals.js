/**
 * Custom Modal System - Teal Theme
 * Modern and professional modals matching the system logo
 */

// Custom Alert Modal
function customAlert(message, title = 'Notice') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999999; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); animation: fadeIn 0.2s ease;';
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 16px; max-width: 500px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: slideUp 0.3s ease; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px 24px;">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;">${title}</h3>
                </div>
                <div style="padding: 24px;">
                    <p style="margin: 0; color: #475569; line-height: 1.6; font-size: 15px;">${message}</p>
                </div>
                <div style="background: #f8fafc; padding: 16px 24px; display: flex; justify-content: flex-end; border-top: 1px solid #e2e8f0;">
                    <button id="customAlertOk" style="padding: 10px 24px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(76, 138, 137, 0.2);" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 138, 137, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(76, 138, 137, 0.2)'">OK</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const okBtn = document.getElementById('customAlertOk');
        const closeModal = () => {
            modal.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                modal.remove();
                resolve();
            }, 200);
        };
        
        okBtn.addEventListener('click', closeModal);
        okBtn.focus();
        
        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    });
}

// Custom Confirm Modal
function customConfirm(message, title = 'Confirm') {
    return new Promise((resolve) => {
        // Generate unique ID for this modal instance
        const modalId = 'customConfirmModal_' + Date.now();
        const okBtnId = 'customConfirmOk_' + Date.now();
        const cancelBtnId = 'customConfirmCancel_' + Date.now();
        
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'custom-confirm-modal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999999; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); animation: fadeIn 0.2s ease;';
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 16px; max-width: 500px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: slideUp 0.3s ease; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px 24px;">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;">${title}</h3>
                </div>
                <div style="padding: 24px;">
                    <p style="margin: 0; color: #475569; line-height: 1.6; font-size: 15px;">${message}</p>
                </div>
                <div style="background: #f8fafc; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0;">
                    <button id="${cancelBtnId}" style="padding: 10px 24px; background: white; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569; font-size: 14px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">Cancel</button>
                    <button id="${okBtnId}" style="padding: 10px 24px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(76, 138, 137, 0.2);" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 138, 137, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(76, 138, 137, 0.2)'">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const okBtn = document.getElementById(okBtnId);
        const cancelBtn = document.getElementById(cancelBtnId);
        
        let resolved = false;
        const closeModal = (result) => {
            if (resolved) return; // Prevent double resolution
            resolved = true;
            modal.style.animation = 'fadeOut 0.2s ease';
            document.removeEventListener('keydown', handleEscape);
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.remove();
                }
                resolve(result);
            }, 200);
        };
        
        okBtn.addEventListener('click', () => closeModal(true));
        cancelBtn.addEventListener('click', () => closeModal(false));
        okBtn.focus();
        
        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal(false);
            }
        };
        document.addEventListener('keydown', handleEscape);
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(false);
            }
        });
    });
}

// Custom Prompt Modal
function customPrompt(message, defaultValue = '', title = 'Input Required') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); animation: fadeIn 0.2s ease;';
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 16px; max-width: 500px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: slideUp 0.3s ease; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px 24px;">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;">${title}</h3>
                </div>
                <div style="padding: 24px;">
                    <p style="margin: 0 0 16px 0; color: #475569; line-height: 1.6; font-size: 15px;">${message}</p>
                    <input type="text" id="customPromptInput" value="${defaultValue}" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='#4c8a89'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div style="background: #f8fafc; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0;">
                    <button id="customPromptCancel" style="padding: 10px 24px; background: white; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569; font-size: 14px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">Cancel</button>
                    <button id="customPromptOk" style="padding: 10px 24px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(76, 138, 137, 0.2);" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 138, 137, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(76, 138, 137, 0.2)'">OK</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const input = document.getElementById('customPromptInput');
        const okBtn = document.getElementById('customPromptOk');
        const cancelBtn = document.getElementById('customPromptCancel');
        
        const closeModal = (result) => {
            modal.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                modal.remove();
                resolve(result);
            }, 200);
        };
        
        okBtn.addEventListener('click', () => closeModal(input.value));
        cancelBtn.addEventListener('click', () => closeModal(null));
        
        input.focus();
        input.select();
        
        // Submit on Enter key
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                closeModal(input.value);
            } else if (e.key === 'Escape') {
                closeModal(null);
            }
        });
        
        // Close on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal(null);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    });
}

// Add CSS animations
if (!document.getElementById('customModalStyles')) {
    const style = document.createElement('style');
    style.id = 'customModalStyles';
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}

// Export for use in other scripts
window.customAlert = customAlert;
window.customConfirm = customConfirm;
window.customPrompt = customPrompt;
