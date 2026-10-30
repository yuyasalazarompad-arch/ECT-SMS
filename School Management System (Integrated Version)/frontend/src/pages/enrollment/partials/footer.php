    </main>

    <footer class="enr-footer">
        <div class="enr-footer__inner">
            <span>Fullbright College &middot; Enrollment &amp; Student Portal &middot; AY <?= enr_e($enrollmentSchoolYear) ?></span>
            <span class="enr-footer__links">
                <a href="./index.php">Home</a>
                <a href="./enroll.php">Enrollment</a>
                <a href="<?= enr_e($enrollmentSignedIn ? '../../../dashboard.php' : enr_login_url()) ?>"><?= $enrollmentSignedIn ? 'My Portal' : 'Student Login' ?></a>
            </span>
        </div>
    </footer>
</body>
</html>
