<?php
require_once './includes/header.php';
require_once './includes/dbcon.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management CRUD</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Poppins', sans-serif; }
    /* Modal overlay styling */
    #editModal { z-index: 10000; }
  </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
  <div class="max-w-4xl mx-auto p-8">
    <h1 class="text-4xl font-extrabold text-center text-gray-800 mb-10">User Management CRUD</h1>

    <!-- Add User Card -->
    <div class="bg-white shadow-xl rounded-xl p-8 mb-10">
      <h2 class="text-2xl font-bold text-gray-700 mb-6">Add User</h2>
      <form id="userForm" class="space-y-6">
        <div>
          <label for="username" class="block text-sm font-medium text-gray-600">Username</label>
          <input type="text" id="username" placeholder="Enter username" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-600">Email</label>
          <input type="email" id="email" placeholder="Enter email" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          <!-- Error message will be shown here if email is not unique -->
          <p id="emailError" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>
        <button type="submit" id="addUserBtn" class="w-full bg-blue-600 text-white py-3 rounded-lg shadow hover:bg-blue-700 transition-colors">
          Add User
        </button>
      </form>
    </div>

    <!-- Search Input -->
    <div class="mb-6">
      <input id="searchInput" type="text" placeholder="Search by username or email..." class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Users Table Card -->
    <div class="bg-white shadow-xl rounded-xl p-8">
      <h2 class="text-2xl font-bold text-gray-700 mb-6">User List</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody id="userTableBody" class="bg-white divide-y divide-gray-200">
            <!-- User rows will be injected here dynamically -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md">
      <h2 class="text-2xl font-bold text-gray-700 mb-6">Edit User</h2>
      <form id="editUserForm" class="space-y-6">
        <input type="hidden" id="editUserId">
        <div>
          <label for="editUsername" class="block text-sm font-medium text-gray-600">Username</label>
          <input type="text" id="editUsername" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div>
          <label for="editEmail" class="block text-sm font-medium text-gray-600">Email</label>
          <input type="email" id="editEmail" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          <!-- Error message for edit form email -->
          <p id="editEmailError" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>
        <div class="flex justify-end space-x-4">
          <button type="button" id="cancelEdit" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">Cancel</button>
          <button type="submit" id="editUserBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition-colors">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Global users array will be loaded from the API
    let users = [];

    // Function to load users from the API
    function loadUsers() {
      $.ajax({
        url: './api/users.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            users = response.users;
            renderUsers();
          } else {
            alert(response.message || 'Error loading users.');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error loading users:', error);
        }
      });
    }

    // Helper function: Truncate long text with ellipsis
    function truncateText(text, maxLength) {
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    // Render the users table; store full username in data attribute for search
    function renderUsers() {
      const userTableBody = document.getElementById('userTableBody');
      userTableBody.innerHTML = '';
      users.forEach(user => {
        const displayName = truncateText(user.username, 20);
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="px-6 py-4 whitespace-nowrap" data-fullname="${user.username}">${displayName}</td>
          <td class="px-6 py-4 whitespace-nowrap">${user.email}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <button onclick="editUser(${user.id})" class="text-blue-600 hover:text-blue-800 mr-3">Edit</button>
            <button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-800">Delete</button>
          </td>
        `;
        userTableBody.appendChild(tr);
      });
    }

    // Filter users based on search input (searches both full username and email)
    function filterUsers() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      document.querySelectorAll('#userTableBody tr').forEach(row => {
        const fullName = row.querySelector('td').getAttribute('data-fullname').toLowerCase();
        const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        row.style.display = (fullName.includes(searchTerm) || email.includes(searchTerm)) ? '' : 'none';
      });
    }

    // Attach search event listener
    document.getElementById('searchInput').addEventListener('keyup', filterUsers);

    // AJAX call to check if an email exists (for add form)
    function checkEmailUnique(email, callback) {
      $.ajax({
        url: './api/check_email.php',
        type: 'GET',
        dataType: 'json',
        data: { email: email },
        success: function(response) {
          callback(response.exists);
        },
        error: function(xhr, status, error) {
          console.error('Error checking email:', error);
          callback(false);
        }
      });
    }

    // On blur event for the add form email input
    $('#email').on('input', function() {
      const email = $(this).val().trim();
      if (email === '') {
        $('#emailError').text('').addClass('hidden');
        $('#userForm button[type="submit"]').prop('disabled', false);
        return;
      }
      checkEmailUnique(email, function(exists) {
        if (exists) {
          $('#emailError').text('Email already exists').removeClass('hidden');
          $('#userForm button[type="submit"]').prop('disabled', true);
        } else {
          $('#emailError').text('').addClass('hidden');
          $('#userForm button[type="submit"]').prop('disabled', false);
        }
      });
    });

    // For the edit form email input, verify uniqueness
    $('#editEmail').on('input', function() {
      const email = $(this).val().trim();
      const currentUserId = $('#editUserId').val();
      if (email === '') {
        $('#editEmailError').text('').addClass('hidden');
        $('#editUserForm button[type="submit"]').prop('disabled', false);
        return;
      }
      // Check if email exists and does not belong to the current user.
      $.ajax({
        url: './api/check_email.php',
        type: 'GET',
        dataType: 'json',
        data: { email: email },
        success: function(response) {
          // If the email exists, we need to see if it belongs to another user.
          if (response.exists) {
            // Find the user with this email from the global users array.
            const existingUser = users.find(u => u.email.toLowerCase() === email.toLowerCase());
            if (existingUser && existingUser.id != currentUserId) {
              $('#editEmailError').text('Email already exists').removeClass('hidden');
              $('#editUserForm button[type="submit"]').prop('disabled', true);
              return;
            }
          }
          $('#editEmailError').text('').addClass('hidden');
          $('#editUserForm button[type="submit"]').prop('disabled', false);
        },
        error: function(xhr, status, error) {
          console.error('Error checking email:', error);
        }
      });
    });

    // Add user form submission using AJAX
    document.getElementById('userForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const username = document.getElementById('username').value;
      const email = document.getElementById('email').value;
      $.ajax({
        url: './api/users.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ username, email }),
        success: function(response) {
          if (response.success) {
            loadUsers();
            document.getElementById('userForm').reset();
          } else {
            alert('Error adding user: ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('Error adding user:', error);
        }
      });
    });

    // Edit user function: populate modal with selected user data
    function editUser(id) {
      const user = users.find(u => u.id === id);
      if (user) {
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email;
        $('#editEmailError').text('').addClass('hidden');
        $('#editUserForm button[type="submit"]').prop('disabled', false);
        document.getElementById('editModal').classList.remove('hidden');
      }
    }

    // Delete user function using AJAX (soft delete)
    function deleteUser(id) {
      if (confirm('Are you sure you want to delete this user?')) {
        $.ajax({
          url: './api/users.php?id=' + id,
          type: 'DELETE',
          success: function(response) {
            if (response.success) {
              loadUsers();
            } else {
              alert('Error deleting user: ' + response.message);
            }
          },
          error: function(xhr, status, error) {
            console.error('Error deleting user:', error);
          }
        });
      }
    }

    // Handle edit user form submission using AJAX
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const id = Number(document.getElementById('editUserId').value);
      const username = document.getElementById('editUsername').value;
      const email = document.getElementById('editEmail').value;
      $.ajax({
        url: './api/users.php',
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ id, username, email }),
        success: function(response) {
          if (response.success) {
            loadUsers();
            document.getElementById('editModal').classList.add('hidden');
          } else {
            alert('Error updating user: ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          console.error('Error updating user:', error);
        }
      });
    });

    // Cancel editing: hide modal
    document.getElementById('cancelEdit').addEventListener('click', function() {
      document.getElementById('editModal').classList.add('hidden');
    });

    // Expose functions for inline event handlers
    window.editUser = editUser;
    window.deleteUser = deleteUser;

    // On page load, fetch users from the API
    document.addEventListener('DOMContentLoaded', loadUsers);
  </script>
</body>
</html>
<?php sqlsrv_close($conn); ?>
