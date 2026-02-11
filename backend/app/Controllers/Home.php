<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // Create necessary directories if they don't exist
        $this->createUploadDirectories();
        
        // Create default file icon if it doesn't exist
        $this->createDefaultFileIcon();
        
        return redirect()->to('/login');
    }
    
    private function createUploadDirectories()
    {
        $directories = [
            ROOTPATH . 'public/uploads',
            ROOTPATH . 'public/uploads/avatars',
            ROOTPATH . 'public/uploads/groups',
            ROOTPATH . 'public/uploads/messages'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    private function createDefaultFileIcon()
    {
        $fileIconPath = ROOTPATH . 'public/uploads/file-icon.png';
        $defaultAvatarPath = ROOTPATH . 'public/uploads/avatars/default-avatar.png';
        $defaultGroupPath = ROOTPATH . 'public/uploads/groups/default-group.png';
        
        // Create default file icon
        if (!file_exists($fileIconPath)) {
            // Create a simple file icon
            $img = imagecreatetruecolor(100, 100);
            $white = imagecolorallocate($img, 255, 255, 255);
            $blue = imagecolorallocate($img, 0, 102, 204);
            $darkBlue = imagecolorallocate($img, 0, 51, 153);
            
            // Fill background
            imagefill($img, 0, 0, $white);
            
            // Draw file shape
            imagefilledrectangle($img, 20, 10, 80, 90, $blue);
            imagefilledrectangle($img, 20, 10, 60, 30, $darkBlue);
            
            // Save the image
            imagepng($img, $fileIconPath);
            imagedestroy($img);
        }
        
        // Create default avatar
        if (!file_exists($defaultAvatarPath)) {
            $img = imagecreatetruecolor(200, 200);
            $bg = imagecolorallocate($img, 240, 240, 240);
            $textColor = imagecolorallocate($img, 100, 100, 100);
            
            // Fill background
            imagefill($img, 0, 0, $bg);
            
            // Draw circle
            $circleColor = imagecolorallocate($img, 220, 220, 220);
            imagefilledellipse($img, 100, 100, 150, 150, $circleColor);
            
            // Draw text
            imagestring($img, 5, 80, 90, "User", $textColor);
            
            // Save the image
            imagepng($img, $defaultAvatarPath);
            imagedestroy($img);
        }
        
        // Create default group image
        if (!file_exists($defaultGroupPath)) {
            $img = imagecreatetruecolor(200, 200);
            $bg = imagecolorallocate($img, 240, 240, 240);
            $textColor = imagecolorallocate($img, 100, 100, 100);
            
            // Fill background
            imagefill($img, 0, 0, $bg);
            
            // Draw circle
            $circleColor = imagecolorallocate($img, 200, 230, 255);
            imagefilledellipse($img, 100, 100, 150, 150, $circleColor);
            
            // Draw text
            imagestring($img, 5, 75, 90, "Group", $textColor);
            
            // Save the image
            imagepng($img, $defaultGroupPath);
            imagedestroy($img);
        }
    }
}

