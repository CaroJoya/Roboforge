<?php
// Complete tag library for ROBOFORGE
$tagsLibrary = [
    ['tag' => 'arduino', 'suggested_caption' => 'Building with Arduino Uno! Check out this robot project #arduino'],
    ['tag' => 'raspberrypi', 'suggested_caption' => 'Raspberry Pi powered robot with computer vision capabilities #raspberrypi'],
    ['tag' => '3dprinting', 'suggested_caption' => 'Custom 3D printed robot chassis designed in Fusion 360 #3dprinting'],
    ['tag' => 'robotics', 'suggested_caption' => 'Industrial robotics arm with 6 degrees of freedom #robotics'],
    ['tag' => 'ai', 'suggested_caption' => 'Machine learning algorithms powering autonomous navigation #ai'],
    ['tag' => 'diy', 'suggested_caption' => 'DIY robot project using recycled materials and components #diy'],
    ['tag' => 'sensors', 'suggested_caption' => 'Ultrasonic, IR, and LIDAR sensors for obstacle detection #sensors'],
    ['tag' => 'servo', 'suggested_caption' => 'High torque servo motors for precise movement control #servo'],
    ['tag' => 'esp32', 'suggested_caption' => 'ESP32 WiFi enabled robot with IoT capabilities #esp32'],
    ['tag' => 'bldc', 'suggested_caption' => 'BLDC motors with ESC controllers for high speed robots #bldc'],
    ['tag' => 'ros', 'suggested_caption' => 'Robot Operating System (ROS) integration for complex behaviors #ros'],
    ['tag' => 'opencv', 'suggested_caption' => 'OpenCV based object detection and tracking system #opencv'],
    ['tag' => 'drone', 'suggested_caption' => 'Quadcopter drone with stabilized flight controller #drone'],
    ['tag' => 'hexapod', 'suggested_caption' => 'Six-legged walking robot with inverse kinematics #hexapod'],
    ['tag' => 'linefollower', 'suggested_caption' => 'High speed line following robot with PID control #linefollower'],
    ['tag' => 'sumorobot', 'suggested_caption' => 'Competition ready sumo robot with aggressive tactics #sumorobot'],
    ['tag' => 'pickandplace', 'suggested_caption' => 'Automated pick and place robot arm for warehouse #pickandplace'],
    ['tag' => 'swarm', 'suggested_caption' => 'Swarm robotics with multiple coordinated robots #swarm'],
    ['tag' => 'autonomous', 'suggested_caption' => 'Self-driving robot with path planning algorithms #autonomous'],
    ['tag' => 'bluetooth', 'suggested_caption' => 'Smartphone controlled robot via Bluetooth module #bluetooth'],
    ['tag' => 'lidar', 'suggested_caption' => 'LIDAR mapping and SLAM for environment reconstruction #lidar'],
    ['tag' => 'gps', 'suggested_caption' => 'GPS guided outdoor robot for waypoint navigation #gps'],
    ['tag' => 'bionic', 'suggested_caption' => 'Bionic prosthetic hand with EMG muscle sensors #bionic'],
    ['tag' => 'waterproof', 'suggested_caption' => 'Underwater ROV for marine exploration #waterproof'],
    ['tag' => 'voicecontrol', 'suggested_caption' => 'Voice controlled robot using speech recognition #voicecontrol'],
    ['tag' => 'gesture', 'suggested_caption' => 'Gesture controlled robot with accelerometer sensors #gesture'],
    ['tag' => 'maze', 'suggested_caption' => 'Maze solving robot using flood fill algorithm #maze'],
    ['tag' => 'combat', 'suggested_caption' => 'Combat robot with weapon systems for competitions #combat'],
    ['tag' => 'education', 'suggested_caption' => 'Educational robot kit for teaching programming #education'],
    ['tag' => 'opensource', 'suggested_caption' => 'Open source hardware and software design files #opensource']
];

// Function to get suggested caption based on selected tags
function getSuggestedCaption($selectedTags, $tagsLibrary) {
    if (empty($selectedTags)) {
        return '';
    }
    
    $firstTag = $selectedTags[0];
    foreach ($tagsLibrary as $tagData) {
        if ($tagData['tag'] === $firstTag) {
            return $tagData['suggested_caption'];
        }
    }
    return '';
}

// Function to search posts by tags
function searchPostsByTags($pdo, $searchTerm) {
    $searchTerm = '%' . $searchTerm . '%';
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_photo 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.tags LIKE ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>