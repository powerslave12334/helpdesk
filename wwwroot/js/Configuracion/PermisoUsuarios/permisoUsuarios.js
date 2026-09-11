document.addEventListener("DOMContentLoaded", function () {
    const userFilter = document.getElementById("IdUsuarioFiltro");
    const checkAll = document.getElementById("checkAllPermissions");
    const permissionChecks = Array.from(document.querySelectorAll(".permission-check"));
    const rowChecks = Array.from(document.querySelectorAll(".row-check"));

    if (userFilter) {
        userFilter.addEventListener("change", function () {
            userFilter.form.submit();
        });
    }

    if (checkAll) {
        checkAll.addEventListener("change", function () {
            permissionChecks.forEach(function (checkbox) {
                checkbox.checked = checkAll.checked;
            });

            rowChecks.forEach(function (checkbox) {
                checkbox.checked = checkAll.checked;
                checkbox.indeterminate = false;
            });
        });
    }

    rowChecks.forEach(function (rowCheck) {
        const row = rowCheck.closest("tr");
        const checks = row ? Array.from(row.querySelectorAll(".permission-check")) : [];

        rowCheck.addEventListener("change", function () {
            checks.forEach(function (checkbox) {
                checkbox.checked = rowCheck.checked;
            });

            rowCheck.indeterminate = false;
            updateMasterCheck();
        });

        checks.forEach(function (checkbox) {
            checkbox.addEventListener("change", function () {
                updateRowCheck(rowCheck, checks);
                updateMasterCheck();
            });
        });

        updateRowCheck(rowCheck, checks);
    });

    updateMasterCheck();

    function updateRowCheck(rowCheck, checks) {
        const checked = checks.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        rowCheck.checked = checks.length > 0 && checked === checks.length;
        rowCheck.indeterminate = checked > 0 && checked < checks.length;
    }

    function updateMasterCheck() {
        if (!checkAll || permissionChecks.length === 0) {
            return;
        }

        const checked = permissionChecks.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        checkAll.checked = checked === permissionChecks.length;
        checkAll.indeterminate = checked > 0 && checked < permissionChecks.length;
    }
});
