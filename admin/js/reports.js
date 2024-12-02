jQuery(document).ready(function($) {
    // Load Chart.js
    if (typeof Chart === 'undefined') {
        return;
    }

    // Donations over time chart
    const donationsCtx = document.getElementById('donations-chart').getContext('2d');
    new Chart(donationsCtx, {
        type: 'line',
        data: donationsChartData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Payment methods chart
    const methodsCtx = document.getElementById('payment-methods-chart').getContext('2d');
    new Chart(methodsCtx, {
        type: 'pie',
        data: paymentMethodsData,
        options: {
            responsive: true
        }
    });
}); 