package com.ccb.server;

import java.io.File;
import java.util.Iterator;
import org.dom4j.Document;
import org.dom4j.Element;
import org.dom4j.io.SAXReader;

public class Service {
    protected static int PORT;
    protected static int MAXCONN;
    protected static String PUBKEY = "";
    private static String XMLCONFIG = "";

    protected void setXmlPATH(String xmlPath) {
        XMLCONFIG = xmlPath;
    }

    protected void setMAXCONN() {
        try {
            File f = new File(XMLCONFIG);

            SAXReader reader = new SAXReader();
            Document doc = reader.read(f);
            Element root = doc.getRootElement();
            for (Iterator i = root.elementIterator("maxconn"); i.hasNext();) {
                Element foo = (Element) i.next();

                String strPort = foo.elementText("value");

                MAXCONN = Integer.valueOf(strPort).intValue();
            }
        } catch (Exception e) {
            e.printStackTrace();
            MAXCONN = 0;
        }
    }

    protected void setPORT() {
        try {
            File f = new File(XMLCONFIG);
            SAXReader reader = new SAXReader();
            Document doc = reader.read(f);
            Element root = doc.getRootElement();
            for (Iterator i = root.elementIterator("commport"); i.hasNext();) {
                Element foo = (Element) i.next();

                String strPort = foo.elementText("value");

                PORT = Integer.valueOf(strPort).intValue();
            }
        } catch (Exception e) {
            e.printStackTrace();
            PORT = 55533;
        }
    }

    protected void setPubKeyByPosid(String strPosID) {
        try {
            File f = new File(XMLCONFIG);
            SAXReader reader = new SAXReader();
            Document doc = reader.read(f);
            Element root = doc.getRootElement();

            boolean isExsitPosId = false;
            for (Iterator i = root.elementIterator("merpos"); i.hasNext();) {
                Element foo = (Element) i.next();

                String xmlPosid = foo.elementText("posid");
                if (strPosID.equals(xmlPosid)) {
                    PUBKEY = foo.elementText("pubkey");
                    if ((PUBKEY == null) || (PUBKEY.length() == 0)) {
                        PUBKEY = "";
                        break;
                    }
                    isExsitPosId = true;
                    break;
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
            PUBKEY = "";
        }
    }
}
