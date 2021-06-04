<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="shortcut icon" href="https://txc.gtimg.com/data/298698/2021/0107/0dd5dc1ffd10f6151cc333c35bb36381.jpeg" type="https://txc.gtimg.com/data/298698/2021/0107/0dd5dc1ffd10f6151cc333c35bb36381.jpeg">
  <link rel="icon" href="https://txc.gtimg.com/data/298698/2021/0107/0dd5dc1ffd10f6151cc333c35bb36381.jpeg" type="https://txc.gtimg.com/data/298698/2021/0107/0dd5dc1ffd10f6151cc333c35bb36381.jpeg">
  <meta charset="UTF-8">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aplayer@1.10.1/dist/APlayer.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 - 页面丢失啦~</title>
</head>
<body>
<div class="main-container">
  <div class="img-container container-item">
    <img src="https://raw.githubusercontent.com/boxcheese/404-/main/image/404.svg">
  </div>

  <div class="text-container container-item">
    <div class="code">404</div>
    <div class="msg">你好像跳到了不该跳的地方...</div>
    <a class="action" href="#">
      <div>返回首页,查看更多内容.</div>
    </a>
    <!-- /#page-content-wrapper -->
    <div id="aplayer" class="aplayer" data-order="random" data-id="3779629" data-server="netease" data-type="playlist" data-fixed="true" data-autoplay="true" data-volume="0.9"></div>
    <!-- aplayer -->

    <script src="https://cdn.staticfile.org/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aplayer@1.10.1/dist/APlayer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/meting@1.2.0/dist/Meting.min.js"></script>
    <!-- end_aplayer -->
    <div style="text-align:center;">
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
    <script type="text/javascript">
    </script>
  </div>
</div>
</body>
<style>
*{
padding:0;
margin:0;
}

html, body{
height:100%;
}

body{
/* 修改背景色调 */
background:rgba(223, 223, 255, 0.39);
display:flex;
justify-content:center; /*body内容水平居中显示*/
align-items:center; /*body内容垂直居中显示*/
}

.main-container{
width:80%;
max-width:1000px;
max-height:500px;
min-width:600px;
background-color:white;
font-size:0;
border-radius:20px;
box-shadow:0 0 50px 0 rgba(146, 146, 146, 0.63);
}
.main-container .container-item{
display:inline-block;
vertical-align:middle;
width:50%;
}

.main-container .img-container{
background-color:rgba(253, 216, 168, 0.692);
border-top-left-radius:20px;
border-bottom-left-radius:20px;
}

.main-container .text-container .code{
font-size:clamp(150px, 20vw, 200px);
font-family:'Arial Narrow';
color:rgb(86, 86, 253);
font-weight:bolder;
text-align:center;
}

.main-container .text-container .msg{
font-size:18px;
text-align:center;
font-weight:bold;
margin-bottom:20px;
}

.main-container .text-container .action{
color:#0f0f0f;
font-size:15px;
font-weight:600;
text-align:center;
text-decoration-line:none;
}
.main-container .text-container a:hover{
color:#5bc0de;
}

#aplayer{
z-index:20000000;
}


</style>
</html>
