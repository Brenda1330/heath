<?php
// File: templates/_profile_dropdown.php

// This component should only display if a user is actually logged in.
if (isset($_SESSION['user_id'])): 
?>

<!-- Profile Dropdown (Top Right) -->
<div class="profile-dropdown">
    <div class="dropdown">
        <!-- The button that triggers the dropdown -->
        <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle me-2"></i>
            <!-- Display the user's name dynamically -->
            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
        </button>

        <!-- The dropdown menu itself -->
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            
            <?php
            // --- START: ROLE-BASED LOGIC ---
            // Check if the 'role' session variable is set
            if (isset($_SESSION['role'])):

                // If the user is a 'doctor', show the Edit Profile link
                if ($_SESSION['role'] === 'doctor'):
            ?>
                    <li><a class="dropdown-item" href="doc_userprofile.php"><i class="fas fa-user-edit me-2"></i> Edit Profile</a></li>
                    <li><hr class="dropdown-divider"></li>

            <?php 
                // You could add other roles here if needed
                // elseif ($_SESSION['role'] === 'admin'):
                // ... link for admin ...
                
                endif; // End of role check
            endif; // End of isset check
            ?>

            <!-- The Logout link is always visible for any logged-in user -->
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<?php 
endif; // End of the main check to see if user is logged in
?>