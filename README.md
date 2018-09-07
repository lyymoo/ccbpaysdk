## 建设银行龙支付-无感支付接口实现

> 建设银行龙支付竟然不提供线上可用的开发SDK，所以自己写了一个

## 运行环境
- PHP 5.0+
- jdk 1.6.0 - 1.8.0

## 接口说明
- 无感支付预查询(prequery.php)
- 无感支付扣费(dopay.php)

## 配置说明
- 支付网关配置(api.php)
- 网关密钥配置(ccb_socket_server\ccbnetpayconfig.xml)

## Socket服务
- socket服务为php网关提供加密验签服务
- 使用ccb_socket_server下的代码打包成ccbnetpaysign.jar包
```
jar cvfm ccbnetpaysign.jar META-INF\MANIFEST.MF class_folder_1 ... class_folder_n
```

- 输入如下命令运行即可
```
#!/bin/sh
nohup java -jar ccbnetpaysign.jar &
```

## 代码贡献
由于测试及使用环境的限制，本项目中只开发了「无感支付停车费」业务场景下的相关支付网关。

如果您有其它支付网关的需求，或者发现本项目中需要改进的代码，**_欢迎 Fork 并提交 PR！_**

## LICENSE
MIT