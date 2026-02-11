<?php

/**
 * Helper file for API testing and debugging
 */

if (!function_exists('test_api_connection')) {
    /**
     * Tests the API connection by making a simple request to the test endpoint
     * 
     * @return array Connection test results
     */
    function test_api_connection()
    {
        $url = base_url('api/test');
        
        // Use cURL to make a request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return [
            'success' => ($status == 200),
            'status' => $status,
            'result' => $result,
            'error' => $error
        ];
    }
}

if (!function_exists('test_message_saving')) {
    /**
     * Tests the message saving API by sending a test message
     * 
     * @param string $endpoint The API endpoint to test
     * @param int $userId The user ID to use as sender
     * @param int $receiverId The receiver ID
     * @return array Test results
     */
    function test_message_saving($endpoint, $userId, $receiverId)
    {
        $url = base_url($endpoint);
        
        // Create test message data
        $data = [
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'content' => 'Test message from API test at ' . date('Y-m-d H:i:s'),
            'type' => 'text',
            'is_group' => 0,
        ];
        
        // Convert to JSON
        $jsonData = json_encode($data);
        
        // Use cURL to make a request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return [
            'success' => ($status == 200),
            'status' => $status,
            'result' => $result,
            'error' => $error,
            'data_sent' => $data
        ];
    }
}
