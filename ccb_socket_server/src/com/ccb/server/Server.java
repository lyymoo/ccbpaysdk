package com.ccb.server;

import java.io.IOException;
import java.net.BindException;
import java.net.ServerSocket;
import java.net.Socket;

public class Server {
    protected ServerSocket serverSocket;

    public void acceptClient() {
        Socket client = null;
        try {
            serverSocket = new ServerSocket(Service.PORT);
            for (;;) {
                client = serverSocket.accept();

                handleClient(client);
            }
        } catch (BindException be) {
            System.out.println("Unable to bind to port " + Service.PORT);
        } catch (IOException e) {
            System.out.println("Unable to instantiate a ServerSocket on port: " + Service.PORT);
        } finally {
            try {
                if (client != null) {
                    client.close();
                }
                if (serverSocket != null) {
                    serverSocket.close();
                }
            } catch (IOException e) {
                e.printStackTrace();
            }
        }
    }

    public void handleClient(Socket client) {
        PooledClientHandler.processRequest(client);
    }

    public void setUpHandlers() {
        for (int i = 0; i < Service.MAXCONN; i++) {
            PooledClientHandler currentHandler = new PooledClientHandler();
            Thread thread = new Thread(currentHandler, "Handler " + i);
            thread.setDaemon(true);
            thread.start();
        }
    }

    public static void main(String[] args) throws Exception {
        String xmlPath = System.getProperty("user.dir") + "/ccbnetpayconfig.xml";
        if (args.length > 0) {
            xmlPath = args[0];
        }
        Service srv = new Service();
        srv.setXmlPATH(xmlPath);
        srv.setPORT();
        srv.setMAXCONN();
        System.out.println("server is running on PORT: " + Service.PORT);
        Server clentServer = new Server();
        clentServer.setUpHandlers();
        clentServer.acceptClient();
    }
}
