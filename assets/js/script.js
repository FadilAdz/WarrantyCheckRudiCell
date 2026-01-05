/**
 * Main JavaScript untuk Rudi Cell Warranty System
 */

// Auto-hide alert setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Konfirmasi sebelum delete
function confirmDelete(message) {
    return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
}

// Format nomor HP otomatis
function formatPhoneInput(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.startsWith('0')) {
        value = '62' + value.substring(1);
    } else if (!value.startsWith('62')) {
        value = '62' + value;
    }
    
    input.value = value;
}

// Validasi form input service
function validateServiceForm() {
    const namaCustomer = document.getElementById('nama_customer');
    const nomorHp = document.getElementById('nomor_hp');
    const jenisHp = document.getElementById('jenis_hp');
    const keluhan = document.getElementById('keluhan');
    
    let isValid = true;
    let errors = [];
    
    // Validasi nama customer
    if (namaCustomer.value.trim() === '') {
        errors.push('Nama customer harus diisi');
        namaCustomer.classList.add('is-invalid');
        isValid = false;
    } else {
        namaCustomer.classList.remove('is-invalid');
    }
    
    // Validasi nomor HP
    if (nomorHp.value.trim() === '') {
        errors.push('Nomor HP harus diisi');
        nomorHp.classList.add('is-invalid');
        isValid = false;
    } else if (!/^(08|62)[0-9]{8,13}$/.test(nomorHp.value.replace(/\D/g, ''))) {
        errors.push('Format nomor HP tidak valid');
        nomorHp.classList.add('is-invalid');
        isValid = false;
    } else {
        nomorHp.classList.remove('is-invalid');
    }
    
    // Validasi jenis HP
    if (jenisHp.value.trim() === '') {
        errors.push('Jenis HP harus diisi');
        jenisHp.classList.add('is-invalid');
        isValid = false;
    } else {
        jenisHp.classList.remove('is-invalid');
    }
    
    // Validasi keluhan
    if (keluhan.value.trim() === '') {
        errors.push('Keluhan harus diisi');
        keluhan.classList.add('is-invalid');
        isValid = false;
    } else {
        keluhan.classList.remove('is-invalid');
    }
    
    // Tampilkan error jika ada
    if (!isValid) {
        alert('Mohon lengkapi form dengan benar:\n\n' + errors.join('\n'));
    }
    
    return isValid;
}

// Search/Filter table
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) { // Skip header row
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// Copy kode garansi ke clipboard
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        alert('Kode garansi berhasil disalin: ' + text);
    } catch (err) {
        alert('Gagal menyalin kode garansi');
    }
    
    document.body.removeChild(textarea);
}

// Print nota/detail service
function printDetail() {
    window.print();
}

// Format input tanggal (set max date = today)
document.addEventListener('DOMContentLoaded', function() {
    const tanggalInput = document.getElementById('tanggal_service');
    if (tanggalInput) {
        const today = new Date().toISOString().split('T')[0];
        tanggalInput.setAttribute('max', today);
    }
});

// Auto-calculate tanggal expired
function calculateExpiredDate() {
    const tanggalService = document.getElementById('tanggal_service');
    const masaGaransi = document.getElementById('masa_garansi_hari');
    const tanggalExpired = document.getElementById('tanggal_expired_display');
    
    if (tanggalService && masaGaransi && tanggalExpired) {
        if (tanggalService.value && masaGaransi.value) {
            const serviceDate = new Date(tanggalService.value);
            const garantiDays = parseInt(masaGaransi.value);
            
            const expiredDate = new Date(serviceDate);
            expiredDate.setDate(expiredDate.getDate() + garantiDays);
            
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            tanggalExpired.textContent = expiredDate.toLocaleDateString('id-ID', options);
        }
    }
}

// Event listener untuk auto-calculate expired date
document.addEventListener('DOMContentLoaded', function() {
    const tanggalService = document.getElementById('tanggal_service');
    const masaGaransi = document.getElementById('masa_garansi_hari');
    
    if (tanggalService && masaGaransi) {
        tanggalService.addEventListener('change', calculateExpiredDate);
        masaGaransi.addEventListener('input', calculateExpiredDate);
    }
});

// Loading overlay
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-overlay';
    loadingDiv.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    loadingDiv.innerHTML = `
        <div style="text-align: center; color: white;">
            <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Memproses data...</p>
        </div>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Export data dengan loading
function exportData(url) {
    showLoading();
    window.location.href = url;
    setTimeout(hideLoading, 2000);
}

// Confirm delete all trash
function confirmDeleteAllTrash() {
    const confirmed = confirm(
        'PERINGATAN!\n\n' +
        'Anda akan menghapus SEMUA data di trash secara PERMANEN.\n' +
        'Data yang sudah dihapus TIDAK BISA dikembalikan!\n\n' +
        'Apakah Anda yakin ingin melanjutkan?'
    );
    
    if (confirmed) {
        const doubleConfirm = confirm(
            'Konfirmasi sekali lagi:\n\n' +
            'Hapus semua data di trash secara permanen?'
        );
        return doubleConfirm;
    }
    
    return false;
}

// Real-time character counter untuk textarea
function updateCharCount(textareaId, counterId, maxLength) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    
    if (textarea && counter) {
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.textContent = currentLength + '/' + maxLength;
            
            if (currentLength >= maxLength) {
                counter.style.color = 'red';
            } else if (currentLength >= maxLength * 0.9) {
                counter.style.color = 'orange';
            } else {
                counter.style.color = '#6c757d';
            }
        });
    }
}

// Initialize character counters
document.addEventListener('DOMContentLoaded', function() {
    updateCharCount('keluhan', 'keluhan-count', 500);
    updateCharCount('data_transaksi', 'transaksi-count', 1000);
});

// Sidebar toggle untuk mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Close sidebar saat klik di luar (mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.navbar-toggler');
    
    if (sidebar && toggleBtn) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// Prevent form double submission
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
            }
        });
    });
});

// Tooltips initialization (Bootstrap)
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Status badge color
function getStatusBadgeClass(sisaHari) {
    if (sisaHari < 0) {
        return 'badge bg-danger';
    } else if (sisaHari <= 3) {
        return 'badge bg-warning text-dark';
    } else if (sisaHari <= 7) {
        return 'badge bg-info';
    } else {
        return 'badge bg-success';
    }
}

console.log('Rudi Cell Warranty System - JavaScript Loaded Successfully ✓');