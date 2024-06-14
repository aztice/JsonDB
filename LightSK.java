/*
Java实现-启灵域界科技
算法基于 aztice的JsonDB-LightSK
 */
package com.APRT.utmLogin;

public class LightSK {
    private String key;

    public LightSK(String key) {
        this.key = key;
    }

    public String encrypt(String message) {
        StringBuilder encryptedMessage = new StringBuilder();
        String key = this.key;

        for (int i = 0; i < message.length(); i += 8) {
            String block = message.substring(i, Math.min(i + 8, message.length()));
            StringBuilder encryptedBlock = new StringBuilder();
            for (int j = 0; j < block.length(); j++) {
                encryptedBlock.append((char) (block.charAt(j) ^ key.charAt(j % key.length())));
            }
            encryptedMessage.append(encryptedBlock);
            key = updateKey(key, block);
        }

        return encryptedMessage.toString();
    }

    public String decrypt(String encryptedMessage) {
        StringBuilder decryptedMessage = new StringBuilder();
        String key = this.key;

        for (int i = 0; i < encryptedMessage.length(); i += 8) {
            String block = encryptedMessage.substring(i, Math.min(i + 8, encryptedMessage.length()));
            StringBuilder decryptedBlock = new StringBuilder();
            for (int j = 0; j < block.length(); j++) {
                decryptedBlock.append((char) (block.charAt(j) ^ key.charAt(j % key.length())));
            }
            decryptedMessage.append(decryptedBlock);
            key = updateKey(key, decryptedBlock.toString());
        }

        return decryptedMessage.toString();
    }

    private String updateKey(String key, String block) {
        StringBuilder updatedKey = new StringBuilder();
        for (int i = 0; i < key.length(); i++) {
            updatedKey.append((char) (key.charAt(i) ^ block.charAt(i % block.length())));
        }
        return updatedKey.toString();
    }
}
/*
//如何调用？
public class Main {
    public static void main(String[] args) {
        LightSK lightSK = new LightSK("mykey");

        String message = "Hello, World!";
        String encryptedMessage = lightSK.encrypt(message);
        System.out.println("Encrypted Message: " + encryptedMessage);

        String decryptedMessage = lightSK.decrypt(encryptedMessage);
        System.out.println("Decrypted Message: " + decryptedMessage);
    }
}
 */
