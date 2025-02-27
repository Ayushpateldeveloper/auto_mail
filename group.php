<?php
ob_start();  // Start output buffering
require_once 'includes/dbcon.php';
include 'includes/header.php';

// Fetch groups
$groups = [];
$sql = 'SELECT * FROM groups';
$result = sqlsrv_query($conn, $sql);
if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $groups[] = $row;
}
sqlsrv_free_stmt($result);

// Fetch members and keywords for each group
foreach ($groups as &$group) {
    // Fetch members
    $sql = 'SELECT * FROM members WHERE group_id = ?';
    $params = array($group['id']);
    $membersResult = sqlsrv_query($conn, $sql, $params);
    $group['members'] = [];
    if ($membersResult !== false) {
        while ($memberRow = sqlsrv_fetch_array($membersResult, SQLSRV_FETCH_ASSOC)) {
            $group['members'][] = $memberRow;
        }
        sqlsrv_free_stmt($membersResult);
    }

    // Fetch keywords
    $sql = 'SELECT * FROM group_keywords WHERE group_id = ?';
    $params = array($group['id']);
    $keywordsResult = sqlsrv_query($conn, $sql, $params);
    $group['keywords'] = [];
    if ($keywordsResult !== false) {
        while ($keywordRow = sqlsrv_fetch_array($keywordsResult, SQLSRV_FETCH_ASSOC)) {
            $group['keywords'][] = $keywordRow;
        }
        sqlsrv_free_stmt($keywordsResult);
    }
}
unset($group);  // Clear the reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Group & Member Management</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
  <div class="container mx-auto px-4 py-8 space-y-10">
    <!-- Top Cards: Create Group, Add Keyword, and Add Member -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Create Group Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Create Group</h2>
        <form id="createGroupForm" class="space-y-4">
          <input type="hidden" name="action" value="create_group">
          <input type="text" name="group_name" placeholder="Group Name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button type="submit" class="w-full bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-150 flex justify-center items-center">
            <i class="fas fa-plus mr-2"></i> Create Group
          </button>
        </form>
      </div>
      
      <!-- Add Keyword Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Add Keyword(s)</h2>
        <form id="addKeywordForm" class="space-y-4">
          <input type="hidden" name="action" value="add_keyword">
          <input type="text" id="keywordInput" name="keyword" placeholder="Enter keywords, separated by commas" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <div id="keywordInputBubbles" class="flex flex-wrap gap-3"></div>
          <!-- Use group_id to match API -->
          <select name="group_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select Group</option>
            <?php foreach ($groups as $group): ?>
              <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="w-full bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-150 flex justify-center items-center">
            <i class="fas fa-plus mr-2"></i> Add Keyword(s)
          </button>
        </form>
      </div>
      
      <!-- Add Member Card with Dynamic Rows -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Add Member(s)</h2>
        <form id="addMemberForm" class="space-y-4">
          <input type="hidden" name="action" value="add_member">
          <select name="group_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select Group</option>
            <?php foreach ($groups as $group): ?>
              <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
            <?php endforeach; ?>
          </select>
          <!-- Dynamic Member Rows Container -->
          <div id="memberRowsContainer" class="space-y-3"></div>
          <button type="submit" class="w-full bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-150 flex justify-center items-center">
            <i class="fas fa-user-plus mr-2"></i> Add Member(s)
          </button>
        </form>
      </div>
    </div>
    
    <!-- Full Width Card: Groups Table -->
    <div class="bg-white shadow-md rounded-lg p-6">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">Groups, Keywords & Members</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Group Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Keywords</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Members</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($groups) == 0): ?>
            <tr>
              <td colspan="4" class="px-6 py-4 text-center text-gray-600">No groups found.</td>
            </tr>
            <?php else: ?>
              <?php foreach ($groups as $group): ?>
              <tr class="hover:bg-gray-100 transition">
                <td class="px-6 py-4 whitespace-nowrap text-gray-800"><?php echo htmlspecialchars($group['name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-800">
                  <?php
        if (empty($group['keywords'])) {
            echo '<span class="text-gray-500">No keywords</span>';
        } else {
            $kwArray = array_column($group['keywords'], 'keyword');
            echo htmlspecialchars(implode(', ', $kwArray));
        }
        ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-800">
                  <?php if (empty($group['members'])): ?>
                    <span class="text-gray-500">No members</span>
                  <?php else: ?>
                    <ul class="space-y-1">
                      <?php foreach ($group['members'] as $member): ?>
                        <li class="flex items-center justify-between">
                          <span><?php echo htmlspecialchars($member['name'] . ' (' . $member['email'] . ')'); ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex space-x-2">
                    <button onclick="openEditGroupModal(<?php echo $group['id']; ?>)" class="text-blue-600 hover:text-blue-800 flex items-center">
                      <i class="fas fa-edit mr-1"></i> Edit Group
                    </button>
                    <button onclick="deleteGroup(<?php echo $group['id']; ?>)" class="text-red-600 hover:text-red-800 flex items-center">
                      <i class="fas fa-trash-alt mr-1"></i> Delete Group
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Edit Group Modal -->
  <div id="editGroupModal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
    <div class="bg-white shadow-xl rounded-lg w-11/12 md:w-1/2 p-6">
      <h2 class="text-2xl font-bold mb-4">Edit Group</h2>
      <form id="editGroupForm" class="space-y-4">
        <input type="hidden" name="action" value="edit_group">
        <input type="hidden" name="group_id" id="editGroupId">
        <div>
          <label class="block text-gray-700">Group Name</label>
          <input type="text" name="group_name" id="editGroupName" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-gray-700">Keywords (comma separated)</label>
          <input type="text" name="keywords" id="editGroupKeywords" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <!-- Member editing section with dynamic rows -->
        <div id="editMembersSection" class="space-y-4">
          <label class="block text-gray-700">Members</label>
          <div id="editMembersContainer"></div>
          <button type="button" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition" onclick="addEditMemberRow()">
            <i class="fas fa-plus mr-1"></i> Add Member
          </button>
        </div>
        <div class="flex justify-end space-x-4 pt-4">
          <button type="button" onclick="closeEditGroupModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Delegated event handlers for dynamic row removal
    $(document).on("click", ".remove-member-row", function() {
      $(this).closest(".member-row").remove();
    });
    $(document).on("click", ".remove-edit-member-row", function() {
      $(this).closest(".edit-member-row").remove();
    });
    
    // Store groups data in a JavaScript object
    var groupsData = <?php echo json_encode($groups); ?>;
    
    // ---------- Edit Group Modal Functions ----------
    function openEditGroupModal(groupId) {
      var group = groupsData.find(g => g.id == groupId);
      if (!group) return;
      $("#editGroupId").val(group.id);
      $("#editGroupName").val(group.name);
      var keywords = group.keywords.map(kw => kw.keyword).join(", ");
      $("#editGroupKeywords").val(keywords);
      
      $("#editMembersContainer").empty();
      if (group.members && group.members.length > 0) {
        group.members.forEach(function(member) {
          var row = `
            <div class="edit-member-row flex gap-3 items-center">
              <input type="hidden" name="members[${member.id}][id]" value="${member.id}">
              <input type="text" name="members[${member.id}][name]" value="${member.name}" placeholder="Member Name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <input type="email" name="members[${member.id}][email]" value="${member.email}" placeholder="Member Email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <button type="button" class="add-edit-member-row bg-green-600 text-white px-3 py-2 rounded-full hover:bg-green-700 transition" onclick="addEditMemberRow()">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          `;
          $("#editMembersContainer").append(row);
        });
      }
      // Always add one blank row for new members.
      addEditMemberRow();
      $("#editGroupModal").removeClass("hidden");
    }
    function closeEditGroupModal() {
      $("#editGroupModal").addClass("hidden");
    }
    
    // ---------- Edit Modal Dynamic Rows Function ----------
    function addEditMemberRow() {
      // Convert any existing plus button in the edit modal to a remove button.
      var plusBtn = $("#editMembersContainer").find("button.add-edit-member-row");
      if (plusBtn.length > 0) {
        plusBtn.removeClass("add-edit-member-row bg-green-600")
               .addClass("remove-edit-member-row bg-red-600")
               .html('<i class="fas fa-times"></i>')
               .off("click");
      }
      var newRow = `
        <div class="edit-member-row flex gap-3 items-center mt-2">
          <input type="hidden" name="members[][id]" value="">
          <input type="text" name="members[][name]" placeholder="Member Name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <input type="email" name="members[][email]" placeholder="Member Email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button type="button" class="add-edit-member-row bg-green-600 text-white px-3 py-2 rounded-full hover:bg-green-700 transition" onclick="addEditMemberRow()">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      `;
      $("#editMembersContainer").append(newRow);
    }
    
    // ---------- Add Member Card Dynamic Rows ----------
    function addMemberRow() {
      var plusBtn = $("#memberRowsContainer").find("button.add-member-row");
      if (plusBtn.length > 0) {
        plusBtn.removeClass("add-member-row bg-green-600")
               .addClass("remove-member-row bg-red-600")
               .html('<i class="fas fa-times"></i>')
               .off("click");
      }
      var newRow = `
        <div class="member-row flex gap-3 items-center">
          <input type="text" name="member_name[]" placeholder="Member Name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <input type="email" name="member_email[]" placeholder="Member Email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button type="button" class="add-member-row bg-green-600 text-white px-3 py-2 rounded-full hover:bg-green-700 transition" onclick="addMemberRow()">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      `;
      $("#memberRowsContainer").append(newRow);
    }
    $(document).ready(function(){
      if ($("#memberRowsContainer").children().length === 0) {
        addMemberRow();
      }
    });
    
    // ---------- AJAX Submissions ----------
    $("#createGroupForm").on("submit", function(e) {
      e.preventDefault();
      $.ajax({
        url: 'api/group_api.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(data) { location.reload(); },
        error: function(xhr, status, error) { console.error('Error creating group:', error); }
      });
    });
    
    $(document).ready(function() {
      const keywordInput = $("#keywordInput");
      const keywordInputBubbles = $("#keywordInputBubbles");
      const keywordForm = $("#addKeywordForm");
    
      keywordInput.on("keypress", function(event) {
        if (event.key === "," || event.key === "Enter") {
          event.preventDefault();
          addKeywordBubble();
        }
      });
      keywordInput.on("blur", addKeywordBubble);
    
      function addKeywordBubble() {
        let input = keywordInput.val().trim();
        if (input) {
          let keywords = input.split(",").map(kw => kw.trim()).filter(kw => kw);
          keywords.forEach(function(keyword) { createKeywordBubble(keyword); });
          keywordInput.val("");
        }
      }
      function createKeywordBubble(keyword) {
        let bubble = $("<span>", { class: "bg-blue-100 text-blue-600 px-3 py-1 rounded-full inline-flex items-center" });
        bubble.html(keyword + ' <button type="button" class="ml-2 text-red-500 hover:text-red-700 text-sm">&times;</button>');
        bubble.find("button").on("click", function() { bubble.remove(); });
        keywordInputBubbles.append(bubble);
      }
    
      keywordForm.on("submit", function(e) {
        e.preventDefault();
        keywordForm.find('input[type="hidden"][name="keywords[]"]').remove();
        let bubbles = keywordInputBubbles.find("span");
        if (bubbles.length === 0) { alert("Please enter at least one keyword."); return; }
        bubbles.each(function() {
          let keyword = $(this).text().replace("×", "").trim();
          $("<input>").attr({ type: "hidden", name: "keywords[]", value: keyword }).appendTo(keywordForm);
        });
        $.ajax({
          url: 'api/group_api.php',
          method: 'POST',
          data: keywordForm.serialize(),
          success: function(data) { location.reload(); },
          error: function(xhr, status, error) { console.error('Error adding keywords:', error); }
        });
      });
    });
    
    $("#addMemberForm").on("submit", function(e) {
      e.preventDefault();
      $.ajax({
        url: 'api/group_api.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(data) { location.reload(); },
        error: function(xhr, status, error) { console.error('Error adding member:', error); }
      });
    });
    
    $("#editGroupForm").on("submit", function(e) {
      e.preventDefault();
      $.ajax({
        url: 'api/group_api.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(data) { location.reload(); },
        error: function(xhr, status, error) { console.error('Error editing group:', error); }
      });
    });
    
    function deleteGroup(groupId) {
      if (confirm("Are you sure you want to delete this group? This will also delete all associated members.")) {
        $.ajax({
          url: 'api/group_api.php',
          method: 'POST',
          data: { action: 'delete_group', group_id: groupId },
          success: function(data) { location.reload(); },
          error: function(xhr, status, error) { console.error('Error deleting group:', error); }
        });
      }
    }
    
    $(document).ready(function() {
      $("table").addClass("transition duration-500 ease-in");
    });
  </script>
</body>
</html>
<?php sqlsrv_close($conn); ?>
