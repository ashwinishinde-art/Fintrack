// Store the chart instance globally so we can destroy/update it later
let expenseChart = null;

// Function to fetch data and render/update the chart
async function loadAndRenderChart() {
    try {
        // Fetch data from your PHP endpoint
        const response = await fetch('get_chart_data.php');
        const data = await response.json();

        if (data.error) {
            console.error("Database Error: ", data.error);
            return;
        }

        const ctx = document.getElementById('analytics-bar-chart').getContext('2d');

        // If the chart already exists, destroy it before rendering a new one to prevent overlay glitches
        if (expenseChart !== null) {
            expenseChart.destroy();
        }

        // Create the new chart
        expenseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.categories, // Array of categories (e.g., ['Food', 'Books & Stationery'])
                datasets: [{
                    label: 'Expenditure (₹)',
                    data: data.amounts, // Array of amounts (e.g., [150.00, 200.00])
                    backgroundColor: 'rgba(0, 196, 140, 0.2)', // Matches your emerald-50 theme
                    borderColor: '#00C48C', // Matches your emerald text color
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Hides the top legend to keep it clean like your UI
                    }
                }
            }
        });
    } catch (error) {
        console.error("Error fetching chart data: ", error);
    }
}

// Initial load when the page is ready
document.addEventListener('DOMContentLoaded', () => {
    loadAndRenderChart();
});
document.getElementById('add-expense-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Submit the new expense to your backend
    const response = await fetch('add_expense.php', {
        method: 'POST',
        body: formData
    });
    
    if (response.ok) {
        // Clear the form
        this.reset();
        
        // 🚀 Dynamically reload the chart with the new data
        loadAndRenderChart(); 
    }
});