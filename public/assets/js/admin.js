/* Modern Forum - Admin Dashboard JavaScript */

class AdminDashboard {
    constructor() {
        this.charts = {};
        this.filters = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.setupDataTables();
        this.setupFilters();
        this.setupRealTimeUpdates();
        this.setupBulkActions();
        this.setupExportFunctions();
    }

    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }

        // Mobile sidebar
        const mobileSidebarToggle = document.querySelector('[data-mobile-sidebar-toggle]');
        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', () => {
                this.toggleMobileSidebar();
            });
        }

        // Dashboard widgets
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-widget-refresh]')) {
                this.refreshWidget(e.target.closest('[data-widget-refresh]'));
            }
        });

        // Data table actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-bulk-action]')) {
                this.handleBulkAction(e.target.closest('[data-bulk-action]'));
            }
        });

        // Filter changes
        document.addEventListener('change', (e) => {
            if (e.target.closest('[data-filter]')) {
                this.handleFilterChange(e.target);
            }
        });

        // Search functionality
        const searchInput = document.querySelector('[data-admin-search]');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.handleSearch(e.target.value);
            }, 300));
        }
    }

    initializeCharts() {
        // Initialize Chart.js charts
        const chartElements = document.querySelectorAll('[data-chart]');
        chartElements.forEach(element => {
            const chartType = element.dataset.chart;
            const chartData = JSON.parse(element.dataset.chartData || '{}');
            
            this.createChart(element, chartType, chartData);
        });
    }

    createChart(canvas, type, data) {
        const ctx = canvas.getContext('2d');
        
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                y: {
                    display: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        };

        const chartConfig = {
            type: type,
            data: data,
            options: { ...defaultOptions, ...data.options }
        };

        this.charts[canvas.id] = new Chart(ctx, chartConfig);
    }

    setupDataTables() {
        const tables = document.querySelectorAll('[data-data-table]');
        tables.forEach(table => {
            this.initializeDataTable(table);
        });
    }

    initializeDataTable(table) {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, header.dataset.sortable);
            });
        });

        // Add pagination if needed
        const paginationContainer = table.querySelector('[data-pagination]');
        if (paginationContainer) {
            this.setupPagination(table, paginationContainer);
        }
    }

    sortTable(table, column) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAscending = table.dataset.sortDirection !== 'asc';
        
        rows.sort((a, b) => {
            const aValue = a.querySelector(`[data-sort="${column}"]`)?.textContent || '';
            const bValue = b.querySelector(`[data-sort="${column}"]`)?.textContent || '';
            
            if (isAscending) {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });
        
        rows.forEach(row => tbody.appendChild(row));
        table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
        
        // Update sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        const currentHeader = table.querySelector(`th[data-sortable="${column}"]`);
        if (currentHeader) {
            currentHeader.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        }
    }

    setupPagination(table, container) {
        const rowsPerPage = parseInt(container.dataset.rowsPerPage) || 10;
        const totalRows = table.querySelectorAll('tbody tr').length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }
        
        this.createPaginationControls(container, totalPages, (page) => {
            this.showTablePage(table, page, rowsPerPage);
        });
        
        // Show first page initially
        this.showTablePage(table, 1, rowsPerPage);
    }

    createPaginationControls(container, totalPages, onPageChange) {
        container.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.addEventListener('click', () => {
            const currentPage = parseInt(container.dataset.currentPage) || 1;
            if (currentPage > 1) {
                onPageChange(currentPage - 1);
            }
        });
        container.appendChild(prevBtn);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn';
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                onPageChange(i);
            });
            container.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.addEventListener('click', () => {
            const currentPage = parseInt(container.dataset.currentPage) || 1;
            if (currentPage < totalPages) {
                onPageChange(currentPage + 1);
            }
        });
        container.appendChild(nextBtn);
    }

    showTablePage(table, page, rowsPerPage) {
        const rows = table.querySelectorAll('tbody tr');
        const startIndex = (page - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update pagination controls
        const container = table.querySelector('[data-pagination]');
        if (container) {
            container.dataset.currentPage = page;
            
            // Update active page button
            container.querySelectorAll('.pagination-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent == page) {
                    btn.classList.add('active');
                }
            });
            
            // Update prev/next button states
            const prevBtn = container.querySelector('.pagination-btn:first-child');
            const nextBtn = container.querySelector('.pagination-btn:last-child');
            
            if (prevBtn) prevBtn.disabled = page === 1;
            if (nextBtn) nextBtn.disabled = page === Math.ceil(rows.length / rowsPerPage);
        }
    }

    setupFilters() {
        const filterForm = document.querySelector('[data-filter-form]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
    }

    handleFilterChange(input) {
        const filterName = input.dataset.filter;
        const filterValue = input.value;
        
        this.filters[filterName] = filterValue;
        
        // Auto-apply filter if configured
        if (input.dataset.autoApply === 'true') {
            this.applyFilters();
        }
    }

    async applyFilters() {
        const filterForm = document.querySelector('[data-filter-form]');
        if (!filterForm) return;
        
        const formData = new FormData(filterForm);
        const filters = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch(filterForm.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.updateTableContent(data.html);
                }
            }
        } catch (error) {
            console.error('Filter error:', error);
            this.showNotification('Failed to apply filters', 'error');
        }
    }

    updateTableContent(html) {
        const tableContainer = document.querySelector('[data-table-container]');
        if (tableContainer) {
            tableContainer.innerHTML = html;
            this.setupDataTables(); // Re-initialize data tables
        }
    }

    async handleSearch(query) {
        if (query.length < 2) return;
        
        try {
            const response = await fetch('/api/admin/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({ query })
            });
            
            const data = await response.json();
            if (data.success) {
                this.showSearchResults(data.results);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    showSearchResults(results) {
        let dropdown = document.querySelector('.admin-search-dropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'admin-search-dropdown';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--bg-primary);
                border: 1px solid var(--border-color);
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
            `;
            
            const searchContainer = document.querySelector('[data-admin-search-container]');
            if (searchContainer) {
                searchContainer.style.position = 'relative';
                searchContainer.appendChild(dropdown);
            }
        }
        
        if (results.length === 0) {
            dropdown.innerHTML = '<div style="padding: var(--spacing-md); color: var(--text-muted);">No results found</div>';
        } else {
            dropdown.innerHTML = results.map(result => `
                <a href="${result.url}" style="display: block; padding: var(--spacing-sm) var(--spacing-md); text-decoration: none; color: var(--text-primary); border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 500;">${result.title}</div>
                    <div style="font-size: var(--text-sm); color: var(--text-muted);">${result.description}</div>
                </a>
            `).join('');
        }
        
        dropdown.style.display = 'block';
    }

    setupBulkActions() {
        const selectAllCheckbox = document.querySelector('[data-select-all]');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('[data-row-select]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        
        this.updateBulkActionButtons();
    }

    updateBulkActionButtons() {
        const selectedCount = document.querySelectorAll('[data-row-select]:checked').length;
        const bulkActionButtons = document.querySelectorAll('[data-bulk-action]');
        
        bulkActionButtons.forEach(button => {
            button.disabled = selectedCount === 0;
            button.dataset.selectedCount = selectedCount;
        });
    }

    async handleBulkAction(button) {
        const action = button.dataset.bulkAction;
        const selectedIds = Array.from(document.querySelectorAll('[data-row-select]:checked'))
            .map(checkbox => checkbox.value);
        
        if (selectedIds.length === 0) {
            this.showNotification('Please select items to perform this action', 'warning');
            return;
        }
        
        if (!confirm(`Are you sure you want to ${action} ${selectedIds.length} item(s)?`)) {
            return;
        }
        
        try {
            const response = await fetch('/api/admin/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: action,
                    ids: selectedIds
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.showNotification(`Successfully ${action}ed ${selectedIds.length} item(s)`, 'success');
                this.refreshCurrentPage();
            } else {
                this.showNotification(data.message || 'Bulk action failed', 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            this.showNotification('Bulk action failed', 'error');
        }
    }

    setupExportFunctions() {
        const exportButtons = document.querySelectorAll('[data-export]');
        exportButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportData(button.dataset.export);
            });
        });
    }

    async exportData(format) {
        try {
            const response = await fetch(`/api/admin/export?format=${format}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `export.${format}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                this.showNotification('Export completed successfully', 'success');
            } else {
                this.showNotification('Export failed', 'error');
            }
        } catch (error) {
            console.error('Export error:', error);
            this.showNotification('Export failed', 'error');
        }
    }

    setupRealTimeUpdates() {
        // WebSocket connection for real-time updates
        if (window.WebSocket) {
            this.connectWebSocket();
        }
        
        // Polling fallback
        this.setupPolling();
    }

    connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}/ws/admin`;
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('Admin WebSocket connected');
            };
            
            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleRealTimeUpdate(data);
            };
            
            this.ws.onclose = () => {
                console.log('Admin WebSocket disconnected');
                // Reconnect after 5 seconds
                setTimeout(() => {
                    this.connectWebSocket();
                }, 5000);
            };
            
        } catch (error) {
            console.error('WebSocket connection failed:', error);
        }
    }

    setupPolling() {
        // Poll for updates every 30 seconds
        setInterval(() => {
            this.pollForUpdates();
        }, 30000);
    }

    async pollForUpdates() {
        try {
            const response = await fetch('/api/admin/updates', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.updates) {
                    this.handleRealTimeUpdate(data);
                }
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }

    handleRealTimeUpdate(data) {
        // Update dashboard widgets
        if (data.widgets) {
            this.updateWidgets(data.widgets);
        }
        
        // Update notifications
        if (data.notifications) {
            this.updateNotifications(data.notifications);
        }
        
        // Update charts
        if (data.charts) {
            this.updateCharts(data.charts);
        }
    }

    updateWidgets(widgets) {
        Object.keys(widgets).forEach(widgetId => {
            const widget = document.querySelector(`[data-widget="${widgetId}"]`);
            if (widget) {
                const valueElement = widget.querySelector('[data-widget-value]');
                if (valueElement) {
                    valueElement.textContent = widgets[widgetId].value;
                }
                
                const changeElement = widget.querySelector('[data-widget-change]');
                if (changeElement) {
                    changeElement.textContent = widgets[widgetId].change;
                    changeElement.className = `widget-change ${widgets[widgetId].changeType}`;
                }
            }
        });
    }

    updateNotifications(notifications) {
        notifications.forEach(notification => {
            this.showNotification(notification.message, notification.type);
        });
    }

    updateCharts(charts) {
        Object.keys(charts).forEach(chartId => {
            if (this.charts[chartId]) {
                this.charts[chartId].data = charts[chartId].data;
                this.charts[chartId].update();
            }
        });
    }

    async refreshWidget(widget) {
        const widgetId = widget.dataset.widgetRefresh;
        
        try {
            widget.classList.add('loading');
            
            const response = await fetch(`/api/admin/widget/${widgetId}/refresh`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.updateWidgets({ [widgetId]: data.widget });
                }
            }
        } catch (error) {
            console.error('Widget refresh error:', error);
        } finally {
            widget.classList.remove('loading');
        }
    }

    refreshCurrentPage() {
        window.location.reload();
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        const main = document.querySelector('.admin-main');
        
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('sidebar-collapsed');
        
        // Save state
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('admin-sidebar-collapsed', isCollapsed);
    }

    toggleMobileSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        sidebar.classList.toggle('mobile-open');
    }

    // Utility methods
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    showNotification(message, type = 'info') {
        if (window.modernForum && window.modernForum.showNotification) {
            window.modernForum.showNotification(message, type);
        } else {
            console.log('Admin Notification:', message);
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.admin-layout')) {
        window.adminDashboard = new AdminDashboard();
        
        // Restore sidebar state
        const isCollapsed = localStorage.getItem('admin-sidebar-collapsed') === 'true';
        if (isCollapsed) {
            const sidebar = document.querySelector('.admin-sidebar');
            const main = document.querySelector('.admin-main');
            if (sidebar && main) {
                sidebar.classList.add('collapsed');
                main.classList.add('sidebar-collapsed');
            }
        }
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminDashboard;
}