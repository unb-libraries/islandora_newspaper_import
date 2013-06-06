<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <relatedItem type="host">
        <identifier type="lccn">{$lccn}</identifier>
        <part>
            <detail type="volume">
                <number>{$volume}</number>
            </detail>
            <detail type="issue">
                <number>{$issue}</number>
            </detail>
            <detail type="edition">
                <number>{$edition}</number>
                <caption></caption>
            </detail>
        </part>
    </relatedItem>
    <titleInfo>
        <nonSort>{$non_sort}</nonSort>
        <title>{$sort_title}</title>
    </titleInfo>
    <originInfo>
        <dateIssued encoding="iso8601">{$iso_date}</dateIssued>
    </originInfo>
    <identifier type="PID">{$pid}</identifier>
    <note type="missing">{$missing_pages}</note>
</mods>
