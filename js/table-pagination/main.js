document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.type = 'text/css';
    style.href = '../js/table-pagination/style.css';
    document.head.appendChild(style);

    const ROWS_PER_PAGE = 10;
    const VISIBLE_PAGES = 5;

    const tables = document.querySelectorAll('.js-paginated-table');

    tables.forEach(table => {
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-container';

        table.parentNode.insertBefore(paginationContainer, table.nextSibling);

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let currentPage = 1;
        let rowsPerPage = ROWS_PER_PAGE;

        const paginationControls = `
            <div class="pagination-buttons">
                <button class="pagination-button first-page">«</button>
                <button class="pagination-button prev-page">‹</button>
                <span class="page-numbers"></span>
                <button class="pagination-button next-page">›</button>
                <button class="pagination-button last-page">»</button>
            </div>
            <div class="rows-per-page">
                <select class="rows-per-page-select">
                    <option value="5">5 por página</option>
                    <option value="10" selected>10 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        `;

        paginationContainer.innerHTML = paginationControls;

        const pageNumbers = paginationContainer.querySelector('.page-numbers');
        const firstPageBtn = paginationContainer.querySelector('.first-page');
        const prevPageBtn = paginationContainer.querySelector('.prev-page');
        const nextPageBtn = paginationContainer.querySelector('.next-page');
        const lastPageBtn = paginationContainer.querySelector('.last-page');
        const rowsPerPageSelect = paginationContainer.querySelector('.rows-per-page-select');

        function getTotalPages() {
            return Math.ceil(rows.length / rowsPerPage);
        }

        function displayPage(page) {
            const totalPages = getTotalPages();
            currentPage = Math.max(1, Math.min(page, totalPages));

            rows.forEach(row => row.style.display = 'none');

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            rows.slice(start, end).forEach(row => row.style.display = '');

            updatePaginationControls();
        }

        function updatePaginationControls() {
            const totalPages = getTotalPages();

            pageNumbers.innerHTML = '';
            const startPage = Math.max(1, currentPage - Math.floor(VISIBLE_PAGES / 2));
            const endPage = Math.min(totalPages, startPage + VISIBLE_PAGES - 1);

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = `page-number ${i === currentPage ? 'active' : ''}`;
                pageBtn.addEventListener('click', () => displayPage(i));
                pageNumbers.appendChild(pageBtn);
            }

            firstPageBtn.disabled = currentPage === 1;
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages;
            lastPageBtn.disabled = currentPage === totalPages;
        }

        firstPageBtn.addEventListener('click', () => displayPage(1));
        prevPageBtn.addEventListener('click', () => displayPage(currentPage - 1));
        nextPageBtn.addEventListener('click', () => displayPage(currentPage + 1));
        lastPageBtn.addEventListener('click', () => displayPage(getTotalPages()));

        rowsPerPageSelect.addEventListener('change', (e) => {
            rowsPerPage = parseInt(e.target.value);
            displayPage(1);
        });

        displayPage(1);
    });
});