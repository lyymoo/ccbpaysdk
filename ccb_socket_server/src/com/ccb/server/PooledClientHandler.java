package com.ccb.server;

import CCBSign.RSASig;
import COM.CCB.EnDecryptAlgorithm.MCipherDecryptor;
import COM.CCB.EnDecryptAlgorithm.MCipherEncryptor;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.io.PrintStream;
import java.io.IOException;

import java.net.Socket;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.LinkedList;
import java.util.List;

public class PooledClientHandler implements Runnable {
    private String strRet = "";
    private Socket socketToHandle;
    private static List pool = new LinkedList();

    private void handleConnection() {
        BufferedReader reader;
        PrintWriter writer;
        try {
            reader = new BufferedReader(new InputStreamReader(socketToHandle.getInputStream()));
            String rawMessage = reader.readLine();
            Date d = new Date();
            SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            System.out.println(sdf.format(d));
            System.out.println("REQUEST: " + rawMessage);
            int index = rawMessage.indexOf("|");
            String strType = rawMessage.substring(0, index);
            String queryString = rawMessage.substring(index + 1);
            String bRet = "strType is:SIGN,ENCRYPTOR,DECRYPTOR";
            switch (strType) {
                case "SIGN":
                    bRet = verifySigature(queryString);
                    break;
                case "ENCRYPTOR":
                    bRet = strEncryptor(queryString);
                    break;
                case "DECRYPTOR":
                    bRet = strDecryptor(queryString);
                    break;
                case "PUBKEY":
                    bRet = getPubKey(queryString);
                    break;
                default:
                    break;
            }
            System.out.println("RESULT: " + bRet);
            writer = new PrintWriter(new PrintStream(socketToHandle.getOutputStream()));
            writer.write(bRet + "\n");
            writer.close();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    protected static void processRequest(Socket requestToHandle) {
        synchronized (pool) {
            pool.add(pool.size(), requestToHandle);
            pool.notifyAll();
        }
    }

    private String verifySigature(String queryString) {
        boolean bRet = false;
        RSASig rsa = new RSASig();
        if ((queryString == null) || (queryString.length() == 0)) {
            this.strRet = ("source string is empty " + queryString);
        } else {
            int index = queryString.indexOf("&SIGN=");
            if (index != -1) {
                String strSrc = queryString.substring(0, index);

                String strSign = queryString.substring(index + 6);
                if ((strSrc == null) || (strSrc.length() == 0)) {
                    this.strRet = "sign source string is empty";
                } else if ((strSign == null) || (strSign.length() == 0)) {
                    this.strRet = "sign string is empty";
                } else {
                    int a = queryString.indexOf("&POSID=");
                    String strPosID = queryString.substring(a + 7, a + 16);

                    Service srv = new Service();
                    srv.setPubKeyByPosid(strPosID);
                    if (Service.PUBKEY.length() == 0) {
                        this.strRet = "posid or pubkey is not found, please check ccbnetpayconfig.xml";
                    }
                    rsa.setPublicKey(Service.PUBKEY);
                    bRet = rsa.verifySigature(strSign, strSrc);
                    if (bRet) {
                        this.strRet = "Y";
                    } else {
                        this.strRet = "N";
                    }
                }
            } else {
                this.strRet = ("source string is not standard, source: " + queryString);
            }
        }
        return this.strRet;
    }

    private String strEncryptor(String queryString) {
        int a = queryString.indexOf("&POSID=");
        String strPosID = queryString.substring(a + 7, a + 16);

        Service srv = new Service();
        srv.setPubKeyByPosid(strPosID);
        if (Service.PUBKEY.length() == 0) {
            return "posid or pubkey is not found, please check ccbnetpayconfig.xml";
        }
        //1.创建COM.CCB. MCipherEncryptor对象
        //商户密钥后30位
        String strKey = Service.PUBKEY.substring(Service.PUBKEY.length() - 30);
        //创建加密对象，向构造函数传入密钥
        MCipherEncryptor ccbEncryptor = new MCipherEncryptor(strKey);
        try {
            return ccbEncryptor.doEncrypt(queryString);
        } catch (Exception e) {
            return "doEncrypt except";
        }
    }

    private String strDecryptor(String queryString) {
        int a = queryString.indexOf("&POSID=");
        String strPosID = queryString.substring(a + 7, a + 16);

        Service srv = new Service();
        srv.setPubKeyByPosid(strPosID);
        if (Service.PUBKEY.length() == 0) {
            return "posid or pubkey is not found, please check ccbnetpayconfig.xml";
        }

        String strKey = Service.PUBKEY.substring(Service.PUBKEY.length() - 30);
        int b = queryString.indexOf("&ccbParam=");
        String ccbParam = queryString.substring(b + 10);
        if (a > b) {
            ccbParam = queryString.substring(b + 10, a);
        }
        MCipherDecryptor ccbDecryptor = new MCipherDecryptor(strKey);
        try {
            return ccbDecryptor.doDecrypt(ccbParam);
        } catch (Exception e) {
            return "doDecrypt except";
        }
    }

    private String getPubKey(String queryString) {
        Service srv = new Service();
        srv.setPubKeyByPosid(queryString);
        if (Service.PUBKEY.length() == 0) {
            return "posid or pubkey is not found, please check ccbnetpayconfig.xml";
        }
        return Service.PUBKEY;
    }

    public void run() {
        for (;;) {
            synchronized (pool) {
                //continue;
                try {
                    pool.wait();
                } catch (InterruptedException e) {
                    e.printStackTrace();
                }
                if (pool.isEmpty()) {
                    continue;
                }
                this.socketToHandle = ((Socket) pool.remove(0));
            }
            handleConnection();
        }
    }
}
