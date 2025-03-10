<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

$auth = new Auth($conn);
$auth->requireAdmin();

// Lấy thống kê theo thời gian
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Thống kê tài liệu theo ngày
$document_stats = $conn->prepare("
    SELECT DATE(created_at) as date,
           COUNT(*) as count
    FROM documents
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$document_stats->execute([$start_date, $end_date]);
$documents_by_date = $document_stats->fetchAll();

// Thống kê người dùng mới
$new_users = $conn->prepare("
    SELECT DATE(created_at) as date,
           COUNT(*) as count
    FROM users
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$new_users->execute([$start_date, $end_date]);
$users_by_date = $new_users->fetchAll();

// Top người dùng tích cực
$active_users = $conn->query("
    SELECT u.full_name,
           COUNT(DISTINCT d.id) as document_count,
           COUNT(DISTINCT c.id) as comment_count,
           COUNT(DISTINCT l.id) as like_count
    FROM users u
    LEFT JOIN documents d ON u.id = d.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN likes l ON u.id = l.user_id
    GROUP BY u.id
    ORDER BY (document_count + comment_count + like_count) DESC
    LIMIT 10
")->fetchAll();

// Top tài liệu phổ biến
$popular_docs = $conn->query("
    SELECT d.title,
           u.full_name as author,
           COUNT(DISTINCT l.id) as likes,
           COUNT(DISTINCT c.id) as comments
    FROM documents d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    GROUP BY d.id
    ORDER BY (likes + comments) DESC
    LIMIT 10
")->fetchAll();

require_once 'includes/admin_header.php';
?>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

.table {
    font-size: 0.9rem;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #2c3e50;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-primary {
    background-color: #3498db;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
}

.form-control {
    border-radius: 5px;
    border: 1px solid #ddd;
    padding: 0.5rem 1rem;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

/* Thêm style cho chart container */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
    margin: 20px 0;
}

.card-body {
    padding: 1.5rem;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4">
                <h1 class="h2 fw-bold text-primary">Báo cáo thống kê</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form class="d-flex gap-3 bg-light p-3 rounded">
                        <div class="d-flex align-items-center">
                            <label class="me-2">Từ:</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="d-flex align-items-center">
                            <label class="me-2">Đến:</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Lọc
                        </button>
                    </form>
                </div>
            </div>

            <!-- Biểu đồ thống kê -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-file-alt me-2"></i>
                                Tài liệu mới
                            </h5>
                            <div class="chart-container">
                                <canvas id="documentsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-users me-2"></i>
                                Người dùng mới
                            </h5>
                            <div class="chart-container">
                                <canvas id="usersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng thống kê -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-star me-2"></i>
                                Top người dùng tích cực
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Người dùng</th>
                                            <th class="text-center">Tài liệu</th>
                                            <th class="text-center">Bình luận</th>
                                            <th class="text-center">Lượt thích</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($active_users as $user): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-user-circle me-2"></i>
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </td>
                                            <td class="text-center"><?php echo $user['document_count']; ?></td>
                                            <td class="text-center"><?php echo $user['comment_count']; ?></td>
                                            <td class="text-center"><?php echo $user['like_count']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-crown me-2"></i>
                                Top tài liệu phổ biến
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tài liệu</th>
                                            <th>Tác giả</th>
                                            <th class="text-center">Lượt thích</th>
                                            <th class="text-center">Bình luận</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($popular_docs as $doc): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-alt me-2"></i>
                                                <?php echo htmlspecialchars($doc['title']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($doc['author']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-heart me-1"></i>
                                                    <?php echo $doc['likes']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-comments me-1"></i>
                                                    <?php echo $doc['comments']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Hàm format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.getDate() + '/' + (date.getMonth() + 1);
}

// Hàm format số liệu
function formatNumber(value) {
    if (value < 1000) return value;
    return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Cấu hình chung cho biểu đồ
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                    size: 12,
                    family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                }
            }
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            titleColor: '#2c3e50',
            bodyColor: '#2c3e50',
            borderColor: '#e1e1e1',
            borderWidth: 1,
            padding: 10,
            titleFont: {
                size: 14,
                weight: 'bold'
            },
            bodyFont: {
                size: 13
            },
            callbacks: {
                title: function(tooltipItems) {
                    return 'Ngày: ' + formatDate(tooltipItems[0].label);
                },
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.raw);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                drawBorder: false,
                color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                },
                font: {
                    size: 11
                }
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                callback: function(value, index) {
                    return formatDate(this.getLabelForValue(value));
                },
                maxRotation: 0,
                font: {
                    size: 11
                }
            }
        }
    },
    interaction: {
        intersect: false,
        mode: 'index'
    },
    elements: {
        line: {
            tension: 0.3
        },
        point: {
            radius: 2,
            hoverRadius: 5
        }
    }
};

// Gradient cho biểu đồ tài liệu
const documentsCtx = document.getElementById('documentsChart').getContext('2d');
const documentsGradient = documentsCtx.createLinearGradient(0, 0, 0, 300);
documentsGradient.addColorStop(0, 'rgba(52, 152, 219, 0.2)');
documentsGradient.addColorStop(1, 'rgba(52, 152, 219, 0)');

// Gradient cho biểu đồ người dùng
const usersCtx = document.getElementById('usersChart').getContext('2d');
const usersGradient = usersCtx.createLinearGradient(0, 0, 0, 300);
usersGradient.addColorStop(0, 'rgba(231, 76, 60, 0.2)');
usersGradient.addColorStop(1, 'rgba(231, 76, 60, 0)');

// Khởi tạo biểu đồ tài liệu
const documentsChart = new Chart(
    documentsCtx,
    {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($documents_by_date, 'date')); ?>,
            datasets: [{
                label: 'Số tài liệu mới',
                data: <?php echo json_encode(array_column($documents_by_date, 'count')); ?>,
                borderColor: '#3498db',
                backgroundColor: documentsGradient,
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3498db',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#3498db',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                pointHoverRadius: 5,
            }]
        },
        options: chartOptions
    }
);

// Khởi tạo biểu đồ người dùng
const usersChart = new Chart(
    usersCtx,
    {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($users_by_date, 'date')); ?>,
            datasets: [{
                label: 'Người dùng mới',
                data: <?php echo json_encode(array_column($users_by_date, 'count')); ?>,
                borderColor: '#e74c3c',
                backgroundColor: usersGradient,
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#e74c3c',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#e74c3c',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                pointHoverRadius: 5,
            }]
        },
        options: chartOptions
    }
);

// Animation cho các card khi load trang
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>