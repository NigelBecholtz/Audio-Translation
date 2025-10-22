// Simple Toast Notification System
export class Toast {
    static show(message, type = 'info', duration = 5000) {
        const toastContainer = this.getOrCreateContainer();
        const toast = this.createToast(message, type);
        
        toastContainer.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.add('toast-show');
        }, 10);
        
        // Auto dismiss
        setTimeout(() => {
            this.dismiss(toast);
        }, duration);
        
        return toast;
    }
    
    static success(message, duration) {
        return this.show(message, 'success', duration);
    }
    
    static error(message, duration) {
        return this.show(message, 'error', duration);
    }
    
    static warning(message, duration) {
        return this.show(message, 'warning', duration);
    }
    
    static info(message, duration) {
        return this.show(message, 'info', duration);
    }
    
    static getOrCreateContainer() {
        let container = document.getElementById('toast-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2';
            document.body.appendChild(container);
        }
        
        return container;
    }
    
    static createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} transform translate-x-full transition-transform duration-300`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const colors = {
            success: 'bg-green-500 border-green-600',
            error: 'bg-red-500 border-red-600',
            warning: 'bg-yellow-500 border-yellow-600',
            info: 'bg-blue-500 border-blue-600'
        };
        
        toast.innerHTML = `
            <div class="flex items-center gap-3 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 min-w-[300px] max-w-md">
                <i class="fas ${icons[type]} text-xl"></i>
                <p class="flex-1 font-medium">${message}</p>
                <button onclick="Toast.dismiss(this.closest('.toast'))" class="ml-2 hover:bg-white/20 rounded p-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        return toast;
    }
    
    static dismiss(toast) {
        toast.classList.remove('toast-show');
        toast.classList.add('translate-x-full');
        
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// Add CSS for toast animations
const style = document.createElement('style');
style.textContent = `
    .toast {
        transition: transform 0.3s ease-in-out;
    }
    .toast-show {
        transform: translateX(0) !important;
    }
`;
document.head.appendChild(style);

// Make Toast available globally
window.Toast = Toast;
