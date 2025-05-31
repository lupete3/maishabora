import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.css';
import Chart from 'chart.js/auto';


window.Swal = Swal;

window.Chart = Chart;

window.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('contributionsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.contributionLabels || [],
                datasets: [{
                    label: 'Montant déposé (FC)',
                    data: window.contributionData || [],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' FC';
                            }
                        }
                    }
                }
            }
        });
    }
});
