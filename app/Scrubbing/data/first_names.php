<?php

// Common English first names used to detect real names in rant text. Matching is
// anchored on these (a capitalized word only counts as a name if it's in here),
// which keeps false positives low. Lowercased for case-insensitive lookup.

return [
    'james', 'john', 'robert', 'michael', 'william', 'david', 'richard', 'joseph',
    'thomas', 'charles', 'christopher', 'daniel', 'matthew', 'anthony', 'mark',
    'donald', 'steven', 'paul', 'andrew', 'joshua', 'kenneth', 'kevin', 'brian',
    'george', 'edward', 'ronald', 'timothy', 'jason', 'jeffrey', 'ryan', 'jacob',
    'gary', 'nicholas', 'eric', 'jonathan', 'stephen', 'larry', 'justin', 'scott',
    'brandon', 'benjamin', 'samuel', 'gregory', 'frank', 'alexander', 'raymond',
    'patrick', 'jack', 'dennis', 'jerry', 'tyler', 'aaron', 'jose', 'henry',
    'adam', 'douglas', 'nathan', 'peter', 'zachary', 'kyle', 'walter', 'harold',
    'jeremy', 'carl', 'keith', 'roger', 'gerald', 'ethan', 'arthur', 'terry',
    'sean', 'noah', 'todd', 'chad', 'doug', 'greg', 'trevor', 'brad',
    'mary', 'patricia', 'jennifer', 'linda', 'elizabeth', 'barbara', 'susan',
    'jessica', 'sarah', 'karen', 'nancy', 'lisa', 'betty', 'margaret', 'sandra',
    'ashley', 'kimberly', 'emily', 'donna', 'michelle', 'carol', 'amanda',
    'dorothy', 'melissa', 'deborah', 'stephanie', 'rebecca', 'sharon', 'laura',
    'cynthia', 'kathleen', 'amy', 'angela', 'shirley', 'anna', 'brenda', 'pamela',
    'emma', 'nicole', 'helen', 'samantha', 'katherine', 'christine', 'debra',
    'rachel', 'carolyn', 'janet', 'catherine', 'maria', 'heather', 'diane',
    'ruth', 'julie', 'olivia', 'joyce', 'virginia', 'victoria', 'kelly',
    'lauren', 'christina', 'joan', 'evelyn', 'judith', 'megan', 'cheryl', 'andrea',
    'hannah', 'jacqueline', 'martha', 'gloria', 'teresa', 'ann', 'sara', 'madison',
    'pam', 'janice', 'dwight', 'angela', 'oscar', 'kevin', 'stanley', 'phyllis',
];
