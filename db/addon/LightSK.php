<?php
/*
拓展地址: https://github.com/aztice/LightSK
作者: @aztice
*/
class LightSK {
    private $key;

    // 构造函数，接受密钥作为参数
    public function __construct($key) {
        $this->key = $key;
    }

    // 加密函数
    public function encrypt($message) {
        $encryptedBlocks = [];
        $key = $this->key;

        // 将消息分割成固定长度的块
        $blocks = str_split($message, 8);

        foreach ($blocks as $block) {
            // 对每个块进行加密，使用轻量级异或运算
            $encryptedBlock = '';
            for ($i = 0; $i < strlen($block); $i++) {
                $encryptedBlock .= $block[$i] ^ $key[$i % strlen($key)];
            }
            $encryptedBlocks[] = $encryptedBlock;

            // 更新密钥
            $key = $this->updateKey($key, $block);
        }
        // 返回加密后的结果
        return implode('', $encryptedBlocks);
    }

    // 解密函数
    public function decrypt($encryptedMessage) {
        $decryptedBlocks = [];
        $key = $this->key;

        // 将密文分割成固定长度的块
        $blocks = str_split($encryptedMessage, 8);

        foreach ($blocks as $block) {
            // 对每个块进行解密，使用相同的轻量级异或运算
            $decryptedBlock = '';
            for ($i = 0; $i < strlen($block); $i++) {
                $decryptedBlock .= $block[$i] ^ $key[$i % strlen($key)];
            }
            $decryptedBlocks[] = $decryptedBlock;

            // 更新密钥
            $key = $this->updateKey($key, $decryptedBlock);
        }

        // 返回解密后的结果
        return implode('', $decryptedBlocks);
    }

    // 密钥更新函数
    private function updateKey($key, $block) {
        // 这里可以根据具体需求设计密钥更新逻辑，这里简单地将密钥与块进行异或运算
        $updatedKey = '';
        for ($i = 0; $i < strlen($key); $i++) {
            $updatedKey .= $key[$i] ^ $block[$i % strlen($block)];
        }
        return $updatedKey;
    }
}
