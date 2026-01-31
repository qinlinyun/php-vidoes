<?php
/**
 * 添加下载资源页面
 * 提交后保存到JSON文件
 */
require 'common.php';

$message = '';
$messageType = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证数据
    if (!empty($_POST['name']) && !empty($_POST['url'])) {
        $data = [
            'name' => trim($_POST['name']),
            'url' => trim($_POST['url'])
        ];
        
        // 添加到JSON
        addDownload($data);
        
        $message = '资源添加成功！';
        $messageType = 'success';
        
        // 清空表单
        $_POST = [];
    } else {
        $message = '请填写资源名称和下载链接！';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加下载资源</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            padding: 2rem 1rem;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 2rem;
        }
        
        .form-card {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #eafaf1;
            color: #27ae60;
            border: 1px solid #d5f5e3;
        }
        
        .message.error {
            background-color: #fdedeb;
            color: #e74c3c;
            border: 1px solid #fadbd8;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #27ae60;
        }
        
        .btn-secondary {
            background-color: #ecf0f1;
            color: #7f8c8d;
        }
        
        .btn-secondary:hover {
            background-color: #d5dbdb;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #3498db;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">添加下载资源</h1>
        </div>
        
        <div class="form-card">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="name" class="form-label">资源名称 <span style="color: #e74c3c;">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           placeholder="请输入资源名称">
                </div>
                
                <div class="form-group">
                    <label for="url" class="form-label">下载链接 <span style="color: #e74c3c;">*</span></label>
                    <input type="url" id="url" name="url" class="form-control" 
                           value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : ''; ?>"
                           placeholder="请输入下载链接（http://或https://开头）">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">保存资源</button>
                    <button type="reset" class="btn btn-secondary">重置</button>
                </div>
            </form>
            
            <a href="index.php" class="back-link">← 返回资源列表</a>
        </div>
    </div>
</body>
</html>