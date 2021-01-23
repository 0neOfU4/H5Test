1. 安装Git \\192.168.1.210\shared\tools\Git-2.7.2-64-bit.exe 
2. 安装node.js (\\192.168.1.210\shared\tools\node-v8.0.0-x64.msi)
   装好之后运行	
   npm install -g cnpm --registry=https://registry.npm.taobao.org
   cnpm install -g yarn
   yarn config set registry https://registry.npm.taobao.org -g
   yarn config set sass_binary_site http://cdn.npm.taobao.org/dist/node-sass -g

3. 命令行进入本目录（第一次执行这些，以后就不用了）
yarn install

4. 调试
yarn server
然后打开浏览器 http://localhost:8081  （确保服务器已准备好否则白屏）
	
5. 发布
yarn build
然后在./dist里


6. IDE推荐使用 webstorm 
	\\192.168.1.210\shared\tools\vs\webstorm