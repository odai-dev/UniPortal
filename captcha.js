class CustomCaptcha {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('CAPTCHA container not found');
            return;
        }
        
        this.puzzleId = null;
        this.selectedTiles = [];
        this.verified = false;
        
        this.init();
    }
    
    init() {
        this.container.innerHTML = `
            <div class="custom-captcha-box">
                <div class="captcha-checkbox-container">
                    <input type="checkbox" id="captcha-checkbox" class="captcha-checkbox" disabled>
                    <label for="captcha-checkbox" class="captcha-label">
                        <span class="captcha-text">I'm not a robot</span>
                    </label>
                    <div class="captcha-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="captcha-modal" id="captcha-modal">
                <div class="captcha-modal-content">
                    <div class="captcha-modal-header">
                        <h5 class="captcha-modal-title">Verify you are human</h5>
                        <button type="button" class="captcha-close" id="captcha-close">&times;</button>
                    </div>
                    <div class="captcha-modal-body">
                        <div class="captcha-instruction" id="captcha-instruction">
                            Select all matching images
                        </div>
                        <div class="captcha-grid" id="captcha-grid">
                            <!-- Tiles will be inserted here -->
                        </div>
                        <div class="captcha-message" id="captcha-message"></div>
                    </div>
                    <div class="captcha-modal-footer">
                        <button type="button" class="btn-captcha-refresh" id="captcha-refresh">
                            <i class="fas fa-sync-alt"></i> New Challenge
                        </button>
                        <button type="button" class="btn-captcha-verify" id="captcha-verify">
                            Verify
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        this.checkbox = document.getElementById('captcha-checkbox');
        this.modal = document.getElementById('captcha-modal');
        this.grid = document.getElementById('captcha-grid');
        this.instruction = document.getElementById('captcha-instruction');
        this.message = document.getElementById('captcha-message');
        
        this.attachEventListeners();
    }
    
    attachEventListeners() {
        const checkboxContainer = this.container.querySelector('.captcha-checkbox-container');
        checkboxContainer.addEventListener('click', (e) => {
            if (!this.verified) {
                this.openModal();
            }
        });
        
        document.getElementById('captcha-close').addEventListener('click', () => {
            this.closeModal();
        });
        
        document.getElementById('captcha-verify').addEventListener('click', () => {
            this.submitVerification();
        });
        
        document.getElementById('captcha-refresh').addEventListener('click', () => {
            this.loadChallenge();
        });
        
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
    }
    
    openModal() {
        this.modal.style.display = 'flex';
        this.loadChallenge();
    }
    
    closeModal() {
        this.modal.style.display = 'none';
    }
    
    async loadChallenge() {
        this.selectedTiles = [];
        this.message.textContent = '';
        this.message.className = 'captcha-message';
        
        try {
            const response = await fetch('custom_captcha.php?action=generate');
            const data = await response.json();
            
            if (data.success) {
                this.puzzleId = data.puzzleId;
                this.instruction.textContent = data.instruction;
                this.renderGrid(data.tiles);
            } else {
                this.showMessage('Failed to load challenge', 'error');
            }
        } catch (error) {
            console.error('Error loading challenge:', error);
            this.showMessage('Error loading challenge', 'error');
        }
    }
    
    renderGrid(tiles) {
        this.grid.innerHTML = '';
        
        tiles.forEach(tile => {
            const tileElement = document.createElement('div');
            tileElement.className = 'captcha-tile';
            tileElement.dataset.index = tile.index;
            
            if (tile.type === 'color') {
                tileElement.style.backgroundColor = tile.color;
                tileElement.classList.add('color-tile');
            } else if (tile.type === 'emoji') {
                tileElement.innerHTML = `<span class="emoji-tile">${tile.emoji}</span>`;
            }
            
            tileElement.addEventListener('click', () => {
                this.toggleTile(tile.index, tileElement);
            });
            
            this.grid.appendChild(tileElement);
        });
    }
    
    toggleTile(index, element) {
        const indexPos = this.selectedTiles.indexOf(index);
        
        if (indexPos > -1) {
            this.selectedTiles.splice(indexPos, 1);
            element.classList.remove('selected');
        } else {
            this.selectedTiles.push(index);
            element.classList.add('selected');
        }
    }
    
    async submitVerification() {
        if (this.selectedTiles.length === 0) {
            this.showMessage('Please select at least one tile', 'error');
            return;
        }
        
        try {
            const response = await fetch('custom_captcha.php?action=verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    puzzleId: this.puzzleId,
                    selectedTiles: this.selectedTiles
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.verified = true;
                this.checkbox.checked = true;
                this.checkbox.disabled = false;
                
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'captcha_token';
                tokenInput.value = data.token;
                tokenInput.id = 'captcha-token-input';
                
                const existingToken = document.getElementById('captcha-token-input');
                if (existingToken) {
                    existingToken.remove();
                }
                
                this.container.appendChild(tokenInput);
                
                this.showMessage('Verification successful!', 'success');
                
                setTimeout(() => {
                    this.closeModal();
                }, 1000);
            } else {
                this.showMessage(data.message || 'Verification failed', 'error');
                setTimeout(() => {
                    this.loadChallenge();
                }, 1500);
            }
        } catch (error) {
            console.error('Error verifying:', error);
            this.showMessage('Error verifying selection', 'error');
        }
    }
    
    showMessage(text, type) {
        this.message.textContent = text;
        this.message.className = `captcha-message ${type}`;
    }
    
    isVerified() {
        return this.verified;
    }
    
    reset() {
        this.verified = false;
        this.checkbox.checked = false;
        this.selectedTiles = [];
        const tokenInput = document.getElementById('captcha-token-input');
        if (tokenInput) {
            tokenInput.remove();
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const captchaContainer = document.getElementById('custom-captcha-container');
    if (captchaContainer) {
        window.customCaptcha = new CustomCaptcha('custom-captcha-container');
        
        const form = captchaContainer.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!window.customCaptcha.isVerified()) {
                    e.preventDefault();
                    alert('Please complete the CAPTCHA verification');
                    return false;
                }
            });
        }
    }
});
