<?php

namespace App\Controllers;

class Setup extends BaseController
{
    public function createDefaultImages()
    {
        // Create necessary directories
        $directories = [
            ROOTPATH . 'public/uploads',
            ROOTPATH . 'public/uploads/avatars',
            ROOTPATH . 'public/uploads/groups',
            ROOTPATH . 'public/uploads/messages'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
                echo "Created directory: $dir<br>";
            } else {
                echo "Directory already exists: $dir<br>";
            }
        }
        
        // Create default avatar
        $defaultAvatarPath = ROOTPATH . 'public/uploads/avatars/default-avatar.png';
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
            echo "Created default avatar image<br>";
        } else {
            echo "Default avatar already exists<br>";
        }
        
        // Create default group image
        $defaultGroupPath = ROOTPATH . 'public/uploads/groups/default-group.png';
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
            echo "Created default group image<br>";
        } else {
            echo "Default group image already exists<br>";
        }
        
        echo "<p>Setup complete!</p>";
        echo "<a href='/chat'>Go to Chat</a>";
        
        return;
    }
}
