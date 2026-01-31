<?php
require 'common.php';
$downloads = getDownloads();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>资源下载中心</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- AOS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <!-- Animate.css -->
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
            padding: 2rem 1rem;
            font-family: system-ui, -apple-system, BlinkMacSystemFont;
        }

        .download-item {
            background: #fff;
            border-radius: 10px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 6px 18px rgba(0,0,0,.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: .3s;
        }

        .download-item:hover {
            transform: translateY(-4px);
        }

        .resource-name {
            font-size: 1.1rem;
            font-weight: 500;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- 顶部 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="animate__animated animate__fadeInDown">📦 资源下载中心</h2>
        <a href="/" class="btn btn-outline-secondary">
            ← 返回主页
        </a>
    </div>

    <!-- 列表 -->
    <div class="row g-3">
        <?php if (!empty($downloads)): ?>
            <?php foreach ($downloads as $item): ?>
                <div class="col-12" data-aos="fade-up">
                    <div class="download-item">
                        <div class="resource-name">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <button
                            class="btn btn-primary download-confirm"
                            data-url="<?php echo htmlspecialchars($item['url']); ?>"
                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                        >
                            下载
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted mt-5">
                <h5>暂无下载资源</h5>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // 初始化 AOS
    AOS.init({
        duration: 700,
        once: true
    });

    // 下载确认
    document.querySelectorAll('.download-confirm').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            const name = this.dataset.name;

            Swal.fire({
                title: '确认下载？',
                html: `<b>${name}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '确认下载',
                cancelButtonText: '取消',
                confirmButtonColor: '#0d6efd'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>

</body>
</html>
