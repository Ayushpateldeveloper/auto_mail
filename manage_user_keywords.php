<?php
require_once './includes/dbcon.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage User Keywords</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Include Select2 CSS and JS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Poppins', sans-serif; }
    /* Ensure modal overlays everything */
    #editModal { z-index: 10000; }
    /* Style Select2 to blend with Tailwind */
    .select2-container .select2-selection--single {
      height: 2.75rem;
      padding: 0.5rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      background-color: #f9fafb;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #374151;
      line-height: 2.75rem;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
  <?php include 'includes/header.php'; ?>

  <div class="max-w-4xl mx-auto p-8">
    <h1 class="text-4xl font-extrabold text-center text-gray-800 mb-10">Manage User Keywords</h1>

    <!-- Add User Card -->
    <div class="bg-white shadow-xl rounded-xl p-8 mb-10">
      <h2 class="text-2xl font-bold text-gray-700 mb-6">Add User</h2>
      <form id="addUserForm" class="space-y-6">
        <!-- Username Field with Select2 -->
        <div>
          <label for="usernameSelect" class="block text-sm font-medium text-gray-600">Username</label>
          <select id="usernameSelect" name="username" class="w-full" required></select>
        </div>
        <!-- Email Field with Uniqueness Check -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-600">Email</label>
          <input type="email" id="email" placeholder="Enter email" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          <p id="emailError" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>
        <!-- Departments Field with Select2 -->
        <div>
          <label for="departmentsSelect" class="block text-sm font-medium text-gray-600 mb-1">Select Departments</label>
          <select id="departmentsSelect" name="departments[]" multiple class="w-full" required>
            <?php
            $sql = 'SELECT id, name FROM departments ORDER BY name';
            $stmt = sqlsrv_query($conn, $sql);
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
              echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . '</option>';
            }
            ?>
          </select>
        </div>
        <!-- Keywords Checkboxes (populated dynamically from departments) -->
        <div id="departmentKeywords" class="mt-4"></div>
        <button type="submit" id="addUserBtn" class="w-full bg-blue-600 text-white py-3 rounded-lg shadow hover:bg-blue-700 transition-colors">
          Add User
        </button>
      </form>
    </div>
  </div>

  <script>
    // Global users array to populate username select options
    let users = [];

    // Function to load users from the API for populating username options
    function loadUsers() {
      $.ajax({
        url: './api/users.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            users = response.users;
            updateUsernameOptions();
          } else {
            alert(response.message || 'Error loading users.');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error loading users:', error);
        }
      });
    }

    // Populate usernameSelect with existing usernames using Select2
    function updateUsernameOptions() {
      const options = users.map(user => ({
        id: user.username,
        text: user.username,
        email: user.email
      }));
      if ($('#usernameSelect').data('select2')) {
        $('#usernameSelect').select2('destroy');
      }
      $('#usernameSelect').select2({
        data: options,
        tags: true,
        placeholder: 'Select or type a username',
        width: '100%'
      });
    }

    // Auto-fill email when an existing username is selected
    $('#usernameSelect').on('select2:select', function(e) {
      const selectedData = e.params.data;
      if (selectedData.email) {
        $('#email').val(selectedData.email);
      } else {
        $('#email').val('');
      }
    });

    // Initialize departments select as Select2
    $('#departmentsSelect').select2({
      placeholder: 'Select Departments',
      width: '100%'
    });

    // When departments are selected, fetch keywords for each and display as checkboxes
    $('#departmentsSelect').on('change', function() {
      const selectedIds = $(this).val();
      if (!selectedIds || selectedIds.length === 0) {
        $('#departmentKeywords').html('');
        return;
      }
      let allKeywords = [];
      let processed = 0;
      selectedIds.forEach(function(deptId) {
        $.ajax({
          url: './api/department_keywords.php',
          type: 'GET',
          dataType: 'json',
          data: { department_id: deptId },
          success: function(response) {
            if (response.success && Array.isArray(response.keywords)) {
              allKeywords = allKeywords.concat(response.keywords);
            }
          },
          complete: function() {
            processed++;
            if (processed === selectedIds.length) {
              // Remove duplicate keywords
              allKeywords = [...new Set(allKeywords)];
              let html = '<div class="mb-4"><span class="block text-sm font-medium text-gray-600 mb-2">Select Keywords</span>';
              allKeywords.forEach(function(keyword) {
                html += `<label class="inline-flex items-center mr-4">
                           <input type="checkbox" name="keywords[]" value="${keyword}" checked class="form-checkbox text-blue-600">
                           <span class="ml-2 text-gray-700">${keyword}</span>
                         </label>`;
              });
              html += '</div>';
              $('#departmentKeywords').html(html);
            }
          },
          error: function(xhr, status, error) {
            console.error('Error loading keywords for department ' + deptId, error);
            processed++;
            if (processed === selectedIds.length) {
              allKeywords = [...new Set(allKeywords)];
              let html = '<div class="mb-4"><span class="block text-sm font-medium text-gray-600 mb-2">Select Keywords</span>';
              allKeywords.forEach(function(keyword) {
                html += `<label class="inline-flex items-center mr-4">
                           <input type="checkbox" name="keywords[]" value="${keyword}" checked class="form-checkbox text-blue-600">
                           <span class="ml-2 text-gray-700">${keyword}</span>
                         </label>`;
              });
              html += '</div>';
              $('#departmentKeywords').html(html);
            }
          }
        });
      });
    });

    // AJAX email uniqueness check for the Add User form on blur
    $('#email').on('blur', function() {
      const email = $(this).val().trim();
      if (email === '') {
        $('#emailError').text('').addClass('hidden');
        $('#addUserBtn').prop('disabled', false);
        return;
      }
      $.ajax({
        url: './api/check_email.php',
        type: 'GET',
        dataType: 'json',
        data: { email: email },
        success: function(response) {
          if (response.success && response.exists) {
            $('#emailError').text('Email already exists').removeClass('hidden');
            $('#addUserBtn').prop('disabled', true);
          } else {
            $('#emailError').text('').addClass('hidden');
            $('#addUserBtn').prop('disabled', false);
          }
        },
        error: function(xhr, status, error) {
          console.error('Error checking email:', error);
        }
      });
    });

    // Add user form submission using AJAX
    $('#addUserForm').on('submit', function(e) {
      e.preventDefault();
      const username = $('#usernameSelect').val();
      const email = $('#email').val();
      const departments = $('#departmentsSelect').val();
      // Collect selected keywords from checkboxes (if any)
      const keywords = [];
      $('input[name="keywords[]"]:checked').each(function() {
        keywords.push($(this).val());
      });
      $.ajax({
        url: './api/users.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ username, email, departments, keywords }),
        success: function(response) {
          if (response.success) {
            alert('User added successfully');
            $('#addUserForm')[0].reset();
            $('#usernameSelect').val(null).trigger('change');
            $('#departmentsSelect').val(null).trigger('change');
            $('#departmentKeywords').html('');
            loadUsers();
          } else {
            alert('Error adding user: ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('Error adding user:', error);
        }
      });
    });

    // On page load, fetch users (to populate username options)
    $(document).ready(function() {
      loadUsers();
      // Initialize usernameSelect with Select2 even if empty initially
      $('#usernameSelect').select2({
        placeholder: 'Select or type a username',
        tags: true,
        width: '100%'
      });
    });
  </script>
</body>
</html>
<?php sqlsrv_close($conn); ?>
