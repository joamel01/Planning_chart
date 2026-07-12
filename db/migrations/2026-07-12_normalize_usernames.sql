UPDATE planner_users
SET username = LOWER(username)
WHERE BINARY username <> BINARY LOWER(username);
