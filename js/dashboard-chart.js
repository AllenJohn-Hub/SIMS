window.initDashboardChart = function() {
    const categoryData = window.categoryData || [];
    const ctx = document.getElementById('categoryChart');
    if (ctx && typeof Chart !== 'undefined') {
        // Destroy previous chart if it exists
        if (window.categoryChartInstance) {
            window.categoryChartInstance.destroy();
        }
        window.categoryChartInstance = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.category),
                datasets: [{
                    data: categoryData.map(item => item.count),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
}; 