<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink">
	<titleInfo>
		<title>Volume {$issue_volume}, Number {$issue_issue} {$issue_edition}</title>
		{if $issue_supplement_title != ''}<subTitle>{$issue_supplement_title}</subTitle>{/if}
	</titleInfo>
	<titleInfo type="alternative">
		<title/>
	</titleInfo>
	<name type="personal">
		<namePart type="given"/>
		<namePart type="family"/>
		<role>
			<roleTerm authority="marcrelator" type="text"/>
		</role>
		<description/>
	</name>
	<typeOfResource>text</typeOfResource>
	<genre authority="marcgt">newspaper</genre>
	<identifier type="issn"/>
	<identifier type="hdl"/>
	<identifier type="lccn"/>
	<identifier type="PID">{$issue_pid}</identifier>
	<abstract/>
	<note type="prospectus"/>
	<originInfo>
		<publisher/>
		<place>
			<placeTerm type="text"/>
		</place>
		<dateIssued>{$iso_date}</dateIssued>
		<dateIssued point="start"/>
		<dateIssued point="end"/>
		<issuance>serial</issuance>
		<frequency authority="marcfrequency"/>
	</originInfo>
	<note type="missing">{$missing_pages}</note>
	<subject>
		<topic/>
	</subject>
	<subject>
		<geographic/>
	</subject>
	<subject>
		<temporal/>
	</subject>
	<relatedItem type="host">
		<identifier type="lccn">{$lccn_id}</identifier>
		<part>
			<detail type="volume">
				<number>{$issue_volume}</number>
			</detail>
			<detail type="issue">
				<number>{$issue_issue}</number>
			</detail>
			<detail type="edition">
				<number>{$issue_edition}</number>
				<caption></caption>
			</detail>
		</part>
	</relatedItem>
	<relatedItem type="succeeding">
		<titleInfo>
			<title/>
			<subTitle/>
		</titleInfo>
		<originInfo>
			<publisher/>
			<place>
				<placeTerm type="text"/>
			</place>
			<issuance>continuing</issuance>
			<dateIssued/>
			<dateIssued point="start"/>
			<dateIssued point="end"/>
			<frequency authority="marcfrequency"/>
		</originInfo>
		<identifier type="issn"/>
	</relatedItem>
</mods>
