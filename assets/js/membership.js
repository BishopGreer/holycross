(function () {
    'use strict';

    var list = document.querySelector('[data-family-members]');
    var addButton = document.querySelector('[data-add-family-member]');

    if (!list || !addButton) {
        return;
    }

    function countMembers() {
        return list.querySelectorAll('[data-family-member]').length;
    }

    function updateMemberTitles() {
        list.querySelectorAll('[data-family-member]').forEach(function (member, index) {
            var legend = member.querySelector('legend');
            if (legend) {
                legend.textContent = 'Household member ' + (index + 1);
            }
        });
    }

    function memberTemplate(index) {
        return '' +
            '<fieldset class="family-member" data-family-member>' +
                '<legend>Household member ' + (index + 1) + '</legend>' +
                '<label for="member_name_' + index + '">Name</label>' +
                '<input id="member_name_' + index + '" name="members[' + index + '][name]">' +
                '<label for="member_preferred_name_' + index + '">Name they would like us to use</label>' +
                '<input id="member_preferred_name_' + index + '" name="members[' + index + '][preferred_name]">' +
                '<div class="settings-grid">' +
                    '<div>' +
                        '<label for="member_pronouns_' + index + '">Pronouns</label>' +
                        '<input id="member_pronouns_' + index + '" name="members[' + index + '][pronouns]">' +
                    '</div>' +
                    '<div>' +
                        '<label for="member_gender_identity_' + index + '">Gender identity</label>' +
                        '<input id="member_gender_identity_' + index + '" name="members[' + index + '][gender_identity]" placeholder="Optional">' +
                    '</div>' +
                '</div>' +
                '<div class="settings-grid">' +
                    '<div>' +
                        '<label for="member_date_of_birth_' + index + '">Date of birth</label>' +
                        '<input id="member_date_of_birth_' + index + '" name="members[' + index + '][date_of_birth]" type="date">' +
                    '</div>' +
                    '<div>' +
                        '<label for="member_relationship_' + index + '">Relationship to household or family</label>' +
                        '<input id="member_relationship_' + index + '" name="members[' + index + '][relationship]" placeholder="Spouse, partner, child, parent, friend, your words">' +
                    '</div>' +
                '</div>' +
                '<label for="member_notes_' + index + '">Notes</label>' +
                '<textarea id="member_notes_' + index + '" name="members[' + index + '][notes]"></textarea>' +
                '<button type="button" class="button secondary remove-family-member" data-remove-family-member>Remove member</button>' +
            '</fieldset>';
    }

    addButton.addEventListener('click', function () {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = memberTemplate(countMembers());
        list.appendChild(wrapper.firstChild);
        updateMemberTitles();
    });

    list.addEventListener('click', function (event) {
        var removeButton = event.target.closest('[data-remove-family-member]');
        if (!removeButton) {
            return;
        }

        var member = removeButton.closest('[data-family-member]');
        if (member) {
            member.remove();
            updateMemberTitles();
        }
    });
})();
