package migrations

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
	"time"

	"github.com/pressly/goose/v3"
	"github.com/sirupsen/logrus"
)

const (
	//StartDate = "2023-06-01"
	StartDate = "2023-01-01"
	//UntilDate = "2023-07-01"
	UntilDate = "2024-01-01"
)

func init() {
	goose.AddMigrationContext(upIsphereStatsRequestsourceData, downIsphereStatsRequestsourceData)
}

func upIsphereStatsRequestsourceData(ctx context.Context, tx *sql.Tx) error {
	// language=sql
	query := `-- @formatter:off
insert into isphere_stats.RequestSource
with
    toDate('{{date_from}}') as date_from,
    toDate('{{date_to}}') as date_to,
    DateSlice as (
        select responseNew.created_date as created_date,
            responseNew.request_id as request_id,
            responseNew.user_id as user_id,
            responseNew.client_id as client_id,
            responseNew.source_name as source_name,
            responseNew.checktype as checktype,
            replaceRegexpOne(responseNew.start_param, '(\\[.+$)', '') as start_param,
            responseNew.process_time as process_time,
            responseNew.res_code as res_code
        from isphere.ResponseNew responseNew
        where responseNew.res_code > 0
            and responseNew.created_date between date_from and date_to
    ),
    RequestDateSlice as (
        select requestNew.created_date as created_date,
            requestNew.id as request_id,
            requestNew.ip
        from isphere.RequestNew requestNew
        where requestNew.id in (
            select dateSlice.request_id
            from DateSlice dateSlice
        )
    ),
    WithUserAndClient as (
        select dateSlice.created_date as created_date,
            dateSlice.request_id as request_id,
            dateSlice.source_name as source_name,
            dateSlice.checktype as checktype,
            dateSlice.start_param as start_param,
            dateSlice.process_time as process_time,
            dateSlice.res_code as res_code,
            user.Id as user_id,
            user.Login as user_login,
            user.AccessArea as user_access_area,
            user.DefaultPrice as user_default_price,
            user.MasterUserId as master_user_id,
            dateSlice.client_id as client_id,
            client.Code as client_code,
            client.Name as client_name,
            anyLast(requestDateSlice.ip) as ip
        from DateSlice dateSlice
        inner join RequestDateSlice requestDateSlice
            using request_id, created_date
        left join isphere_lightweight.SystemUsers user
            on user.Id = dateSlice.user_id
        left join isphere_lightweight.Client client
            on client.id = dateSlice.client_id
        group by dateSlice.created_date,
            dateSlice.request_id,
            dateSlice.source_name,
            dateSlice.checktype,
            dateSlice.start_param,
            dateSlice.process_time,
            dateSlice.res_code,
            user_id,
            user_login,
            user_access_area,
            user_default_price,
            master_user_id,
            dateSlice.client_id,
            client.Code,
            client.Name
    ),
    WithMasterUser as (
        select withUserAndClient.created_date as created_date,
            withUserAndClient.request_id as request_id,
            withUserAndClient.source_name as source_name,
            withUserAndClient.checktype as checktype,
            withUserAndClient.start_param as start_param,
            withUserAndClient.process_time as process_time,
            withUserAndClient.res_code as res_code,
            withUserAndClient.user_id as user_id,
            withUserAndClient.user_login as user_login,
            withUserAndClient.user_default_price as user_default_price,
            masterUser.Id as master_user_id,
            masterUser.Login as master_user_login,
            masterUser.DefaultPrice as master_user_default_price,
            withUserAndClient.client_id as client_id,
            withUserAndClient.client_code as client_code,
            withUserAndClient.client_name as client_name,
            withUserAndClient.ip as ip
        from WithUserAndClient withUserAndClient
        inner join isphere_lightweight.SystemUsers masterUser
            on masterUser.Id = if(
                withUserAndClient.user_id = 623
                    or withUserAndClient.master_user_id is null
                    or withUserAndClient.master_user_id = 0
                    or withUserAndClient.user_access_area > 0,
                withUserAndClient.user_id,
                withUserAndClient.master_user_id
            )
    ),
    WithCustomPrice as (
        select withMasterUser.created_date as created_date,
            withMasterUser.request_id as request_id,
            withMasterUser.source_name as source_name,
            withMasterUser.checktype as checktype,
            withMasterUser.start_param as start_param,
            withMasterUser.process_time as process_time,
            withMasterUser.res_code as res_code,
            withMasterUser.user_id as user_id,
            withMasterUser.user_login as user_login,
            withMasterUser.user_default_price as user_default_price,
            withMasterUser.master_user_id as master_user_id,
            withMasterUser.master_user_login as master_user_login,
            if(userSourcePrice.id > 0, userSourcePrice.price, null) as user_source_price,
            withMasterUser.master_user_default_price as master_user_default_price,
            if(masterUserSourcePrice.id > 0, masterUserSourcePrice.price, null) as master_user_source_price,
            withMasterUser.client_id as client_id,
            withMasterUser.client_code as client_code,
            withMasterUser.client_name as client_name,
            withMasterUser.ip as ip
        from WithMasterUser withMasterUser
        left join isphere_lightweight.UserSourcePrice userSourcePrice
            on userSourcePrice.user_id = withMasterUser.user_id
                and userSourcePrice.source_name = withMasterUser.source_name
        left join isphere_lightweight.UserSourcePrice masterUserSourcePrice
            on masterUserSourcePrice.user_id = withMasterUser.master_user_id
                and masterUserSourcePrice.source_name = withMasterUser.source_name
    )
select withCustomPrice.created_date,
   withCustomPrice.request_id,
   withCustomPrice.source_name,
   withCustomPrice.checktype,
   withCustomPrice.start_param,
   withCustomPrice.process_time,
   withCustomPrice.res_code,
   withCustomPrice.client_id,
   withCustomPrice.client_code,
   withCustomPrice.client_name,
   withCustomPrice.user_id,
   withCustomPrice.user_login,
   withCustomPrice.user_default_price,
   withCustomPrice.user_source_price,
   withCustomPrice.master_user_id,
   withCustomPrice.master_user_login,
   withCustomPrice.master_user_default_price,
   withCustomPrice.master_user_source_price,
   withCustomPrice.ip
from WithCustomPrice withCustomPrice;
-- @formatter:on`

	start, err := time.Parse(time.DateOnly, StartDate)
	if err != nil {
		return fmt.Errorf("failed to parse start date: %w", err)
	}

	until, err := time.Parse(time.DateOnly, UntilDate)
	if err != nil {
		return fmt.Errorf("failed to parse until date: %w", err)
	}

	for ; start.Before(until); start = start.AddDate(0, 1, 0) {
		var (
			from = start.Local()
			to   = from.AddDate(0, 1, 0).
				AddDate(0, 0, -1)
		)

		localQuery := strings.ReplaceAll(query, "{{date_from}}", from.Format(time.DateOnly))
		localQuery = strings.ReplaceAll(localQuery, "{{date_to}}", to.Format(time.DateOnly))

		logrus.Infof("executing statement, from = %s, to = %s\n", from.Format(time.DateOnly), to.Format(time.DateOnly))
		if _, err = tx.ExecContext(ctx, localQuery); err != nil {
			return fmt.Errorf("failed to execute statement: %w", err)
		}
	}

	return nil
}

func downIsphereStatsRequestsourceData(ctx context.Context, tx *sql.Tx) error {
	if _, err := tx.ExecContext(ctx, `truncate table isphere_stats.RequestSource`); err != nil {
		return fmt.Errorf("failed to truncate table: %w", err)
	}

	return nil
}
