// assets/js/app.js

// Toggle submenu
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    if (submenu) {
        submenu.classList.toggle('show');
    }
}

// Theme toggle
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

// Load saved theme on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);
});

// Dynamic row management for Stock In/Out
let rowCount = 0;

function addProductRow(type = 'single') {
    rowCount++;
    const container = document.getElementById('productRows');
    const row = document.createElement('div');
    row.className = 'product-row';
    row.id = `row-${rowCount}`;
    
    let html = `
        <div class="row-actions">
            <button type="button" class="btn btn-sm btn-primary" onclick="cloneRow(${rowCount})">Clone</button>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteRow(${rowCount})">Delete</button>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Product Selection</label>
                <select name="product_id[${rowCount}]" class="form-control" required>
                    <option value="">Select Product</option>
                    ${getProductOptions()}
                </select>
            </div>
            <div class="form-group">
                <label>Qty</label>
                <input type="number" name="qty[${rowCount}]" class="form-control" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Qty Unit</label>
                <select name="qty_unit[${rowCount}]" class="form-control" required>
                    <option value="">Select Unit</option>
                    ${getUnitOptions('Qty_UoM')}
                </select>
            </div>
            <div class="form-group">
                <label>Pkg</label>
                <input type="number" name="pkg[${rowCount}]" class="form-control" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Pkg Unit</label>
                <select name="pkg_unit[${rowCount}]" class="form-control" required>
                    <option value="">Select Unit</option>
                    ${getUnitOptions('Pkg_UoM')}
                </select>
            </div>
        </div>
    `;
    
    row.innerHTML = html;
    container.appendChild(row);
}

function addGroupProductRow() {
    rowCount++;
    const container = document.getElementById('productRows');
    const row = document.createElement('div');
    row.className = 'product-row';
    row.id = `row-${rowCount}`;
    
    let html = `
        <div class="row-actions">
            <button type="button" class="btn btn-sm btn-primary" onclick="cloneRow(${rowCount})">Clone</button>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteRow(${rowCount})">Delete</button>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Group Product Selection</label>
                <select name="group_product_id[${rowCount}]" class="form-control" required>
                    <option value="">Select Group</option>
                    ${getGroupProductOptions()}
                </select>
            </div>
            <div class="form-group">
                <label>Qty</label>
                <input type="number" name="qty[${rowCount}]" class="form-control" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Qty Unit</label>
                <select name="qty_unit[${rowCount}]" class="form-control" required>
                    <option value="">Select Unit</option>
                    ${getUnitOptions('Qty_UoM')}
                </select>
            </div>
            <div class="form-group">
                <label>Pkg</label>
                <input type="number" name="pkg[${rowCount}]" class="form-control" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Pkg Unit</label>
                <select name="pkg_unit[${rowCount}]" class="form-control" required>
                    <option value="">Select Unit</option>
                    ${getUnitOptions('Pkg_UoM')}
                </select>
            </div>
        </div>
    `;
    
    row.innerHTML = html;
    container.appendChild(row);
}

function cloneRow(id) {
    const originalRow = document.getElementById(`row-${id}`);
    const clonedRow = originalRow.cloneNode(true);
    const newId = ++rowCount;
    clonedRow.id = `row-${newId}`;
    
    // Update input names
    const inputs = clonedRow.querySelectorAll('input, select');
    inputs.forEach(input => {
        const name = input.name;
        if (name) {
            input.name = name.replace(id, newId);
        }
    });
    
    document.getElementById('productRows').appendChild(clonedRow);
}

function deleteRow(id) {
    const row = document.getElementById(`row-${id}`);
    if (row) {
        row.remove();
    }
}

// Helper functions to get options (these would be populated from PHP)
function getProductOptions() {
    // This should be populated from database
    return '<option value="1">Product A</option><option value="2">Product B</option>';
}

function getGroupProductOptions() {
    // This should be populated from database
    return '<option value="1">Group 1</option><option value="2">Group 2</option>';
}

function getUnitOptions(type) {
    // This should be populated from database
    if (type === 'Qty_UoM') {
        return '<option value="Pcs">Pcs</option><option value="KG">KG</option><option value="LBS">LBS</option>';
    } else if (type === 'Pkg_UoM') {
        return '<option value="Cartons">Cartons</option><option value="Bags">Bags</option><option value="Dopes">Dopes</option>';
    }
    return '';
}

// Form submission helpers
function clearForm() {
    if (confirm('Are you sure you want to clear the form?')) {
        document.querySelector('form').reset();
        document.getElementById('productRows').innerHTML = '';
        rowCount = 0;
    }
}

// Print functionality
function printDocument(id) {
    window.open(`print_view.php?id=${id}`, '_blank', 'width=800,height=600');
}

// Search functionality
function searchTable(searchInput, tableId) {
    const filter = searchInput.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
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

// Pagination
function paginate(tableId, rowsPerPage) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    const totalPages = Math.ceil((rows.length - 1) / rowsPerPage);
    
    // Create pagination controls
    const paginationDiv = document.getElementById('pagination');
    if (!paginationDiv) return;
    
    paginationDiv.innerHTML = '';
    
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.onclick = () => showPage(tableId, i, rowsPerPage);
        paginationDiv.appendChild(btn);
    }
    
    showPage(tableId, 1, rowsPerPage);
}

function showPage(tableId, pageNum, rowsPerPage) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    const start = (pageNum - 1) * rowsPerPage + 1;
    const end = start + rowsPerPage;
    
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display = (i >= start && i < end) ? '' : 'none';
    }
    
    // Update active button
    const buttons = document.querySelectorAll('#pagination button');
    buttons.forEach((btn, index) => {
        btn.classList.toggle('active', index + 1 === pageNum);
    });
}

// Date picker helper
function setTodayDate(inputId) {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById(inputId).value = today;
}
